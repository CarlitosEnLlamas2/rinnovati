<?php

function buildInscripcionEmail(string $nombre, string $fecha, string $orderId): string {
    $meses = [
        1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',
        7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre'
    ];
    $dt = DateTime::createFromFormat('Y-m-d', $fecha);
    if ($dt) {
        $dia  = (int)$dt->format('d');
        $mes  = $meses[(int)$dt->format('m')];
        $anio = $dt->format('Y');
        $fechaFormateada = "$dia de $mes de $anio";
    } else {
        $fechaFormateada = $fecha;
    }

    $nombreEsc  = htmlspecialchars($nombre,  ENT_QUOTES, 'UTF-8');
    $fechaEsc   = htmlspecialchars($fechaFormateada, ENT_QUOTES, 'UTF-8');
    $orderEsc   = htmlspecialchars($orderId,  ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="es" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Inscripción en proceso — Rinnovati Institute</title>
  <!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#04040A;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<!-- Preheader invisible -->
<div style="display:none;font-size:1px;color:#04040A;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
  Tu inscripción a la Masterclass Técnica PDRN está siendo procesada — te confirmamos en breve.
</div>

<!-- Outer wrapper -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#04040A;">
  <tr>
    <td align="center" style="padding:48px 16px 60px;">

      <!-- Container 580px -->
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:580px;">

        <!-- ── HEADER ── -->
        <tr>
          <td align="center" style="padding-bottom:28px;">
            <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:700;letter-spacing:8px;text-transform:uppercase;color:#C9A84C;">RINNOVATI INSTITUTE</p>
            <!-- Gold line -->
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
              <tr>
                <td width="16" style="background:transparent;height:1px;"></td>
                <td width="48" height="1" style="background-color:#C9A84C;font-size:0;line-height:0;">&nbsp;</td>
                <td width="16" style="background:transparent;height:1px;"></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ── CARD ── -->
        <tr>
          <td style="background-color:#07070F;border:1px solid rgba(201,168,76,0.3);border-radius:16px;padding:48px 44px 44px;">

            <!-- Status badge -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
              <tr>
                <td align="center">
                  <span style="display:inline-block;background-color:#1A1508;border:1px solid rgba(201,168,76,0.35);border-radius:4px;padding:8px 22px;font-family:Arial,Helvetica,sans-serif;font-size:9px;font-weight:700;letter-spacing:5px;text-transform:uppercase;color:#E8C97A;">
                    &#9679;&nbsp;&nbsp;INSCRIPCIÓN EN PROCESO
                  </span>
                </td>
              </tr>
            </table>

            <!-- Main heading -->
            <h1 style="font-family:Arial,Helvetica,sans-serif;font-size:30px;font-weight:700;color:#ffffff;text-align:center;margin:0 0 10px;letter-spacing:-0.5px;text-transform:uppercase;line-height:1.1;">
              Tu lugar está<br>
              <span style="color:#E8C97A;">siendo confirmado</span>
            </h1>
            <p style="font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#666;text-align:center;margin:0 0 36px;letter-spacing:4px;text-transform:uppercase;">Masterclass Técnica PDRN</p>

            <!-- Divider -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
              <tr><td height="1" style="background-color:#161616;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>

            <!-- Greeting -->
            <p style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#aaa;margin:0 0 18px;line-height:1.7;">
              Estimado/a <strong style="color:#ffffff;">{$nombreEsc}</strong>,
            </p>
            <p style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#aaa;margin:0 0 32px;line-height:1.7;">
              Hemos recibido tu solicitud de inscripción para la <strong style="color:#fff;">Masterclass Técnica PDRN</strong>. Tu pago está siendo procesado de forma segura a través de <strong style="color:#fff;">Bold</strong>.
            </p>
            <p style="font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#aaa;margin:0 0 36px;line-height:1.7;">
              Tu inscripción quedará <strong style="color:#ffffff;">oficialmente confirmada</strong> en el momento en que recibas el correo de confirmación de Bold con el recibo de tu transacción.
            </p>

            <!-- Date box -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:32px;">
              <tr>
                <td style="background-color:#0D0D1A;border:1px solid rgba(201,168,76,0.22);border-radius:10px;padding:24px 28px;">
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:9px;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:#C9A84C;margin:0 0 10px;">FECHA RESERVADA</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:26px;font-weight:700;color:#ffffff;margin:0 0 6px;letter-spacing:-0.5px;text-transform:capitalize;">{$fechaEsc}</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#666;margin:0;">Sesión virtual en vivo &nbsp;·&nbsp; Hora de Colombia (COT)</p>
                </td>
              </tr>
            </table>

            <!-- Important notice -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:36px;">
              <tr>
                <td style="background-color:#110F06;border-top:1px solid rgba(232,201,122,0.15);border-right:1px solid rgba(232,201,122,0.15);border-bottom:1px solid rgba(232,201,122,0.15);border-left:3px solid #C9A84C;border-radius:6px;padding:18px 22px;">
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:#E8C97A;margin:0 0 10px;">Importante</p>
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#999;margin:0;line-height:1.8;">
                    Tu inscripción se <strong style="color:#ffffff;">oficializa únicamente con el correo de confirmación de Bold</strong>. Si no lo recibes en los próximos minutos, revisa tu carpeta de spam o contáctanos directamente.
                  </p>
                </td>
              </tr>
            </table>

            <!-- Divider -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
              <tr><td height="1" style="background-color:#161616;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>

            <!-- Next steps heading -->
            <p style="font-family:Arial,Helvetica,sans-serif;font-size:9px;font-weight:700;letter-spacing:5px;text-transform:uppercase;color:#C9A84C;margin:0 0 22px;">QUÉ SIGUE</p>

            <!-- Step 1 -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:18px;">
              <tr>
                <td width="36" valign="top">
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td width="26" height="26" align="center" style="background-color:#1A1508;border:1px solid rgba(201,168,76,0.35);border-radius:50%;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;color:#C9A84C;line-height:26px;">1</td></tr>
                  </table>
                </td>
                <td style="padding-left:12px;padding-top:3px;">
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#999;margin:0;line-height:1.7;"><strong style="color:#ffffff;">Confirma tu pago en Bold</strong> &mdash; Recibirás un correo separado de Bold Co. con el recibo oficial de tu transacción.</p>
                </td>
              </tr>
            </table>

            <!-- Step 2 -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:18px;">
              <tr>
                <td width="36" valign="top">
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td width="26" height="26" align="center" style="background-color:#1A1508;border:1px solid rgba(201,168,76,0.35);border-radius:50%;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;color:#C9A84C;line-height:26px;">2</td></tr>
                  </table>
                </td>
                <td style="padding-left:12px;padding-top:3px;">
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#999;margin:0;line-height:1.7;"><strong style="color:#ffffff;">Recibe tus datos de acceso</strong> &mdash; Una vez confirmado el pago enviaremos el enlace de la sesión y los materiales del programa.</p>
                </td>
              </tr>
            </table>

            <!-- Step 3 -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:40px;">
              <tr>
                <td width="36" valign="top">
                  <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                    <tr><td width="26" height="26" align="center" style="background-color:#1A1508;border:1px solid rgba(201,168,76,0.35);border-radius:50%;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;color:#C9A84C;line-height:26px;">3</td></tr>
                  </table>
                </td>
                <td style="padding-left:12px;padding-top:3px;">
                  <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#999;margin:0;line-height:1.7;"><strong style="color:#ffffff;">Prepárate para la sesión</strong> &mdash; Los cupos son estrictamente limitados. Asegúrate de conectarte puntual el <strong style="color:#E8C97A;text-transform:capitalize;">{$fechaEsc}</strong>.</p>
                </td>
              </tr>
            </table>

            <!-- Divider -->
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-bottom:28px;">
              <tr><td height="1" style="background-color:#161616;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>

            <!-- Order reference -->
            <p style="font-family:Arial,Helvetica,sans-serif;font-size:11px;color:#444;text-align:center;margin:0;">
              Referencia de registro:&nbsp;<span style="color:#666;font-weight:600;letter-spacing:1px;">{$orderEsc}</span>
            </p>

          </td>
        </tr>

        <!-- ── FOOTER ── -->
        <tr>
          <td align="center" style="padding-top:36px;">
            <p style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#555;margin:0 0 6px;">¿Tienes alguna pregunta? Escríbenos a</p>
            <a href="mailto:pagos@rinnovatinstitute.com" style="font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#C9A84C;text-decoration:none;">pagos@rinnovatinstitute.com</a>
            <p style="font-family:Arial,Helvetica,sans-serif;font-size:10px;color:#2E2E2E;margin:24px 0 0;letter-spacing:3px;text-transform:uppercase;">
              &copy; 2025 Rinnovati Institute &nbsp;&middot;&nbsp; rinnovatinstitute.com
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>

</body>
</html>
HTML;
}

function sendInscripcionEmail(string $email, string $nombre, string $fecha, string $orderId): bool {
    $html    = buildInscripcionEmail($nombre, $fecha, $orderId);
    $subject = '=?UTF-8?B?' . base64_encode('Tu inscripción está siendo procesada — Rinnovati Institute') . '?=';
    $from    = $_ENV['ADMISIONES_EMAIL'] ?? 'admisiones@rinnovatinstitute.com';

    $boundary = '----=_Part_' . md5(uniqid());

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "From: Rinnovati Institute <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $plain  = "Estimado/a {$nombre},\r\n\r\n";
    $plain .= "Hemos recibido tu solicitud de inscripción a la Masterclass Técnica PDRN.\r\n";
    $plain .= "Tu inscripción se oficializa con el correo de confirmación de Bold.\r\n\r\n";
    $plain .= "Fecha reservada: {$fecha}\r\n";
    $plain .= "Referencia: {$orderId}\r\n\r\n";
    $plain .= "¿Preguntas? Escríbenos a {$from}\r\n";
    $plain .= "Rinnovati Institute — rinnovatinstitute.com\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($plain)) . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $body .= chunk_split(base64_encode($html)) . "\r\n";
    $body .= "--{$boundary}--";

    return mail($email, $subject, $body, $headers);
}
