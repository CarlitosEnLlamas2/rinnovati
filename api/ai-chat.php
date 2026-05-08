<?php
require_once __DIR__ . '/_helpers.php';

loadEnv(__DIR__ . '/../.env');
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido', 405);

$body    = getJsonBody();
$message = $body['message'] ?? '';
$history = $body['history']  ?? [];

if (!$message) jsonError('Falta el mensaje');

$apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
if (!$apiKey) jsonError('API Key no configurada', 503);

$systemPrompt = 'Eres la asistente virtual de Rinnovati Institute, especializada en la Masterclass Técnica PDRN de Salmón. Responde siempre en español, de forma profesional y cálida. Tu objetivo es resolver dudas de médicos y profesionales de la salud sobre el programa y orientarlos hacia la inscripción.

SOBRE EL PROGRAMA:
- Nombre: Masterclass Técnica PDRN de Salmón
- Empresa: Rinnovati Institute S.A.S. — Medellín, Colombia
- Sitio web: www.rinnovatinstitute.com
- Formato: Sesión privada online de 3 horas, 100% en vivo.
- No hay grabaciones — la filosofía se basa en el debate en vivo y la interacción real

¿QUÉ ES PDRN?
Los polinucleótidos (PDRN) son moléculas activas que reparan el tejido desde el ADN. Se usan en medicina regenerativa y tratamientos estéticos de alto ticket.

PRECIO:
- USD 97 (precio de lanzamiento, fase piloto)
- Precio regular: USD 120
- También disponible en pesos colombianos: lo equivalente en pesos segun la tasa del dia.

QUÉ INCLUYE:
1. Sesión online privada de 3 horas 100% en vivo con la especialista
2. Mapas de Aplicación Médica (rostro, zona periorbitaria, cuello)
3. Algoritmos de Dosificación por crono envejecimiento del paciente
4. Certificación oficial de Rinnovati Institute S.A.S. con validez legal y académica
5. Acceso a debate clínico en tiempo real con la especialista
6. Validación de cédula profesional para garantizar el rigor médico de la sala

FECHAS DISPONIBLES (ciclo actual):
- Mayo 17, 2025 — 2 cupos disponibles
- Mayo 24, 2025 — 4 cupos disponibles
- Junio 5, 2025 — 4 cupos disponibles
Solo 4 fechas al mes para garantizar calidad de mentoría personalizada.

CÓMO INSCRIBIRSE:
El usuario selecciona una fecha → completa el formulario → procesa el pago → recibe confirmación. Si alguien quiere inscribirse, indícale que haga clic en "Ver Fechas y Postular" o "Asegurar mi Acceso Técnico" en la página.

Sé concisa. Usa bullet points cuando ayude a la claridad. No inventes información que no esté aquí.';

// Formatear historial: alternar user/model, empezar con user
$contents = [];
foreach ($history as $m) {
    $role = (($m['role'] ?? 'user') === 'assistant') ? 'model' : 'user';
    $text = $m['text'] ?? '';
    if ($text) $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
}
if (!empty($contents) && $contents[0]['role'] === 'model') array_shift($contents);

$contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

$payload = [
    'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
    'contents'           => $contents,
    'generationConfig'   => ['temperature' => 0.7, 'maxOutputTokens' => 800],
];

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={$apiKey}";

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
]);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    $errData = json_decode($resp, true);
    $errMsg  = $errData['error']['message'] ?? 'Error al consultar la IA';
    error_log("[GEMINI ERROR] $resp");
    jsonError($errMsg, 500);
}

$data      = json_decode($resp, true);
$candidate = $data['candidates'][0] ?? null;

if (($candidate['finishReason'] ?? '') === 'SAFETY') {
    jsonOk(['reply' => 'Lo siento, no puedo responder a eso por políticas de seguridad.']);
}

$reply = $candidate['content']['parts'][0]['text'] ?? 'No recibí respuesta del modelo.';
jsonOk(['reply' => $reply]);
