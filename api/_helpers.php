<?php
// ============================================
// HELPERS — CORS, JSON, ENV, BOLD
// ============================================

function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        if (strlen($value) >= 2) {
            $f = $value[0]; $l = $value[-1];
            if (($f === '"' && $l === '"') || ($f === "'" && $l === "'")) {
                $value = substr($value, 1, -1);
            }
        }
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

function setCorsHeaders(): void {
    $origin = $_ENV['FRONTEND_URL'] ?? '*';
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

function jsonOk(array $data): never {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok' => true], $data));
    exit;
}

function jsonError(string $message, int $status = 400): never {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

function getJsonBody(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function generateOrderId(string $fecha, string $nombre): string {
    $ts   = (int) round(microtime(true) * 1000);
    $slug = strtoupper(substr(preg_replace('/\s+/', '', $nombre), 0, 6));
    return "RIN-{$slug}-{$ts}";
}

function generateIntegrity(string $orderId, int $amount, string $currency): string {
    $secret = $_ENV['BOLD_SECRET_KEY'] ?? '';
    return hash('sha256', "{$orderId}{$amount}{$currency}{$secret}");
}
