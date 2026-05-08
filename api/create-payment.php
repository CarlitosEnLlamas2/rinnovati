<?php
require_once __DIR__ . '/_helpers.php';
require_once __DIR__ . '/_firebase.php';

loadEnv(__DIR__ . '/../.env');
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Método no permitido', 405);

$body = getJsonBody();

$nombre       = trim($body['nombre']       ?? '');
$email        = trim($body['email']        ?? '');
$telefono     = $body['telefono']           ?? null;
$moneda       = strtoupper($body['moneda'] ?? 'COP');
$pais         = $body['pais']               ?? null;
$ciudad       = $body['ciudad']             ?? null;
$especialidad = $body['especialidad']       ?? null;
$institucion  = $body['institucion']        ?? null;
$fecha        = $body['fecha']              ?? '';
$fuente       = $body['fuente']             ?? null;
$firma        = $body['firma']              ?? null;
$fechaFirma   = $body['fechaFirma']         ?? null;
$cert         = $body['certificacion']      ?? [];

if (!$nombre || !$email || !$fecha) {
    jsonError('Faltan campos requeridos: nombre, email, fecha');
}

$PRECIOS = ['COP' => 1_250_000, 'USD' => 297];
if (!isset($PRECIOS[$moneda])) jsonError('Moneda no soportada. Usa COP o USD.');

$amount    = $PRECIOS[$moneda];
$orderId   = generateOrderId($fecha, $nombre);
$hash      = generateIntegrity($orderId, $amount, $moneda);
$isSandbox = ($_ENV['BOLD_ENV'] ?? 'sandbox') !== 'production';
$publicKey = $_ENV['BOLD_PUBLIC_KEY'] ?? '';

firestoreSet('registros', $orderId, [
    'order_id'                  => $orderId,
    'moneda'                    => $moneda,
    'monto'                     => $amount,
    'estado'                    => 'pendiente',
    'nombre'                    => $nombre,
    'email'                     => $email,
    'telefono'                  => $telefono,
    'cedula'                    => null,
    'pais'                      => $pais,
    'ciudad'                    => $ciudad,
    'especialidad'              => $especialidad,
    'registro_medico'           => null,
    'institucion'               => $institucion,
    'fecha_sesion'              => $fecha,
    'fuente'                    => $fuente,
    'firma'                     => $firma,
    'fecha_firma'               => $fechaFirma,
    'cert_medico_titulado'      => !empty($cert['esMedico']),
    'cert_habilitacion_vigente' => !empty($cert['habilitacionVigente']),
    'cert_datos_veridicos'      => !empty($cert['datosVeridicos']),
    'cert_acepta_terminos'      => !empty($cert['aceptaTerminos']),
    'cert_timestamp'            => $cert['timestamp'] ?? date('c'),
    'creado_en'                 => date('c'),
]);

error_log("[PAGO] Nueva orden: $orderId | $nombre | $fecha | $amount $moneda");

jsonOk([
    'orderId'       => $orderId,
    'amount'        => $amount,
    'currency'      => $moneda,
    'integrityHash' => $hash,
    'publicKey'     => $publicKey,
    'description'   => "Masterclass PDRN — $fecha",
    'customerEmail' => $email,
    'customerName'  => $nombre,
    'sandbox'       => $isSandbox,
]);
