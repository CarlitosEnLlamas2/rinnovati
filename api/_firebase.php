<?php
// ============================================
// FIREBASE — FIRESTORE VÍA REST API
// No requiere Composer. Usa OpenSSL + cURL.
// ============================================

function _base64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function getFirebaseAccessToken(): ?string {
    // Cache en archivo temporal para no pedir token en cada request
    $cacheFile = sys_get_temp_dir() . '/fbtoken_' . md5($_ENV['FIREBASE_PROJECT_ID'] ?? 'app') . '.json';

    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached['token']) && ($cached['expiry'] ?? 0) > time() + 60) {
            return $cached['token'];
        }
    }

    $privateKey  = str_replace('\\n', "\n", $_ENV['FIREBASE_PRIVATE_KEY']  ?? '');
    $clientEmail = $_ENV['FIREBASE_CLIENT_EMAIL'] ?? '';

    if (!$privateKey || !$clientEmail) {
        error_log('[FIREBASE] Credenciales no configuradas en .env');
        return null;
    }

    $now    = time();
    $header  = _base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims  = _base64url(json_encode([
        'iss'   => $clientEmail,
        'sub'   => $clientEmail,
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
        'scope' => 'https://www.googleapis.com/auth/datastore',
    ]));
    $input = "$header.$claims";

    $key = openssl_pkey_get_private($privateKey);
    if (!$key) {
        error_log('[FIREBASE] Error cargando la clave privada');
        return null;
    }

    openssl_sign($input, $signature, $key, 'SHA256');
    $jwt = "$input." . _base64url($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT        => 10,
    ]);
    $resp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $token  = $resp['access_token'] ?? null;
    $expiry = $now + ($resp['expires_in'] ?? 3600);

    if ($token) {
        file_put_contents($cacheFile, json_encode(['token' => $token, 'expiry' => $expiry]));
    } else {
        error_log('[FIREBASE] No se obtuvo access_token: ' . json_encode($resp));
    }

    return $token;
}

// Convierte un valor PHP al formato de campo Firestore REST
function _toFsValue(mixed $value): array {
    if ($value === null)      return ['nullValue'    => null];
    if (is_bool($value))      return ['booleanValue' => $value];
    if (is_int($value))       return ['integerValue' => (string) $value];
    if (is_float($value))     return ['doubleValue'  => $value];
    return ['stringValue' => (string) $value];
}

// Convierte array PHP → mapa de fields de Firestore
function _toFsFields(array $data): array {
    $fields = [];
    foreach ($data as $k => $v) {
        $fields[$k] = _toFsValue($v);
    }
    return $fields;
}

// SET completo de un documento (crea o sobreescribe)
function firestoreSet(string $collection, string $docId, array $data): bool {
    $token     = getFirebaseAccessToken();
    $projectId = $_ENV['FIREBASE_PROJECT_ID'] ?? '';

    if (!$token) return false;

    $url  = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}/{$docId}";
    $body = json_encode(['fields' => _toFsFields($data)]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        error_log("[FIREBASE] firestoreSet $collection/$docId falló ($code): $resp");
        return false;
    }
    return true;
}

// UPDATE de campos específicos (no toca los demás)
function firestoreUpdate(string $collection, string $docId, array $updates): bool {
    $token     = getFirebaseAccessToken();
    $projectId = $_ENV['FIREBASE_PROJECT_ID'] ?? '';

    if (!$token) return false;

    $mask = implode('&', array_map(fn($f) => 'updateMask.fieldPaths=' . urlencode($f), array_keys($updates)));
    $url  = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents/{$collection}/{$docId}?{$mask}";
    $body = json_encode(['fields' => _toFsFields($updates)]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => 'PATCH',
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
        ],
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code < 200 || $code >= 300) {
        error_log("[FIREBASE] firestoreUpdate $collection/$docId falló ($code): $resp");
        return false;
    }
    return true;
}
