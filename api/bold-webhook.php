<?php
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/_firebase.php';

loadEnv(__DIR__ . '/../.env');

$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_BOLD_SIGNATURE'] ?? '';
$secret    = $_ENV['BOLD_SECRET_KEY'] ?? '';

$expectedSig = hash_hmac('sha256', $rawBody, $secret);

if (!hash_equals($expectedSig, $signature)) {
    error_log('[WEBHOOK] Firma inválida — posible intento fraudulento');
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Firma inválida']);
    exit;
}

$event = json_decode($rawBody, true);
error_log('[WEBHOOK] Evento recibido: ' . json_encode($event));

$type = $event['type'] ?? '';
$data = $event['data'] ?? [];

if ($type === 'transaction.approved') {
    $orderId  = $data['order_id']          ?? '';
    $amount   = $data['amount']            ?? '';
    $currency = $data['currency']          ?? '';
    $boldTxId = $data['id']               ?? null;
    $email    = $data['customer']['email'] ?? '';

    error_log("✅ PAGO APROBADO: $orderId | $amount $currency | $email");

    firestoreUpdate('registros', $orderId, [
        'estado'     => 'aprobado',
        'bold_tx_id' => $boldTxId,
    ]);
}

if ($type === 'transaction.rejected') {
    $orderId  = $data['order_id'] ?? '';
    $reason   = $data['reason']   ?? '';
    $boldTxId = $data['id']       ?? null;

    error_log("❌ PAGO RECHAZADO: $orderId | Razón: $reason");

    firestoreUpdate('registros', $orderId, [
        'estado'        => 'rechazado',
        'razon_rechazo' => $reason,
        'bold_tx_id'    => $boldTxId,
    ]);
}

header('Content-Type: application/json');
echo json_encode(['received' => true]);
