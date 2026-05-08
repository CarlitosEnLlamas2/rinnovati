<?php
require_once __DIR__ . '/_helpers.php';

loadEnv(__DIR__ . '/../.env');
setCorsHeaders();

$orderId   = $_GET['orderId'] ?? '';
$isSandbox = ($_ENV['BOLD_ENV'] ?? 'sandbox') !== 'production';

header('Content-Type: application/json');
echo json_encode([
    'ok'            => true,
    'orderId'       => $orderId,
    'message'       => 'Consulta el panel Bold para ver el estado en tiempo real',
    'boldDashboard' => $isSandbox
        ? 'https://dashboard.bold.co/sandbox'
        : 'https://dashboard.bold.co',
]);
