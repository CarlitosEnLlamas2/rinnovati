// ============================================
// RINNOVATI INSTITUTE — SERVIDOR DE PAGOS BOLD
// ============================================
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const crypto = require('crypto');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// ── Middlewares ─────────────────────────────
app.use(cors({ origin: process.env.FRONTEND_URL || '*' }));
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// ── Config Bold ─────────────────────────────
const BOLD_SECRET_KEY = process.env.BOLD_SECRET_KEY;
const BOLD_PUBLIC_KEY = process.env.BOLD_PUBLIC_KEY;
const IS_SANDBOX      = process.env.BOLD_ENV !== 'production';

// Bold URLs
const BOLD_BASE = IS_SANDBOX
  ? 'https://checkout.bold.co'          // sandbox usa misma URL, cambia solo la key
  : 'https://checkout.bold.co';

// ============================================
// HELPER: Generar integrity hash para Bold
// SHA-256( orderId + amount + currency + secretKey )
// ============================================
function generateIntegrity(orderId, amount, currency) {
  const raw = `${orderId}${amount}${currency}${BOLD_SECRET_KEY}`;
  return crypto.createHash('sha256').update(raw).digest('hex');
}

// ============================================
// HELPER: Generar order ID único
// ============================================
function generateOrderId(fecha, nombre) {
  const ts   = Date.now();
  const slug = nombre.replace(/\s+/g, '').substring(0, 6).toUpperCase();
  return `RIN-${slug}-${ts}`;
}

// ============================================
// RUTA: Crear sesión de pago Bold
// POST /api/create-payment
// Body: { nombre, email, telefono, cedula, fecha, moneda }
// ============================================
app.post('/api/create-payment', (req, res) => {
  try {
    const { nombre, email, telefono, cedula, fecha, moneda = 'COP' } = req.body;

    // Validaciones básicas
    if (!nombre || !email || !cedula || !fecha) {
      return res.status(400).json({
        ok: false,
        error: 'Faltan campos requeridos: nombre, email, cedula, fecha'
      });
    }

    // Precios según moneda
    const PRECIOS = {
      COP: 1_250_000,   // ← Cambia este valor al precio real en pesos
      USD: 297          // ← Cambia este valor al precio real en dólares
    };

    const monedaUpper = moneda.toUpperCase();
    if (!PRECIOS[monedaUpper]) {
      return res.status(400).json({ ok: false, error: 'Moneda no soportada. Usa COP o USD.' });
    }

    const amount  = PRECIOS[monedaUpper];
    const orderId = generateOrderId(fecha, nombre);
    const hash    = generateIntegrity(orderId, amount, monedaUpper);

    // Datos que el frontend necesita para inicializar el checkout Bold
    const paymentData = {
      ok: true,
      orderId,
      amount,
      currency: monedaUpper,
      integrityHash: hash,
      publicKey: BOLD_PUBLIC_KEY,
      // Metadata visible en tu panel Bold
      description: `Masterclass PDRN — ${fecha}`,
      customerEmail: email,
      customerName: nombre,
      // Sandbox flag
      sandbox: IS_SANDBOX
    };

    console.log(`[PAGO] Nueva orden: ${orderId} | ${nombre} | ${fecha} | ${amount} ${monedaUpper}`);
    res.json(paymentData);

  } catch (err) {
    console.error('[ERROR create-payment]', err);
    res.status(500).json({ ok: false, error: 'Error interno del servidor' });
  }
});

// ============================================
// RUTA: Webhook Bold — Confirmar pago
// POST /api/bold-webhook
// Bold llama esto cuando el pago es aprobado/rechazado
// ============================================
app.post('/api/bold-webhook', express.raw({ type: 'application/json' }), (req, res) => {
  try {
    const signature = req.headers['x-bold-signature'] || '';
    const body      = req.body.toString();

    // Verificar firma del webhook
    const expectedSig = crypto
      .createHmac('sha256', BOLD_SECRET_KEY)
      .update(body)
      .digest('hex');

    if (signature !== expectedSig) {
      console.warn('[WEBHOOK] Firma inválida — posible intento fraudulento');
      return res.status(401).json({ error: 'Firma inválida' });
    }

    const event = JSON.parse(body);
    console.log('[WEBHOOK] Evento recibido:', JSON.stringify(event, null, 2));

    // Procesar evento
    const { type, data } = event;

    if (type === 'transaction.approved') {
      const { order_id, amount, currency, customer } = data;
      console.log(`✅ PAGO APROBADO: ${order_id} | ${amount} ${currency} | ${customer?.email}`);
      // Aquí puedes:
      // - Guardar en base de datos
      // - Enviar email de confirmación
      // - Registrar al médico en la sesión
    }

    if (type === 'transaction.rejected') {
      const { order_id, reason } = data;
      console.log(`❌ PAGO RECHAZADO: ${order_id} | Razón: ${reason}`);
    }

    res.status(200).json({ received: true });

  } catch (err) {
    console.error('[ERROR webhook]', err);
    res.status(500).json({ error: 'Error procesando webhook' });
  }
});

// ============================================
// RUTA: Verificar estado de pago
// GET /api/payment-status/:orderId
// ============================================
app.get('/api/payment-status/:orderId', (req, res) => {
  const { orderId } = req.params;
  // En producción conectarías a Bold API o tu DB
  // Por ahora retorna estructura base
  res.json({
    ok: true,
    orderId,
    message: 'Consulta el panel Bold para ver el estado en tiempo real',
    boldDashboard: IS_SANDBOX
      ? 'https://dashboard.bold.co/sandbox'
      : 'https://dashboard.bold.co'
  });
});

// ============================================
// RUTA: Servir landing page
// GET /
// ============================================
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// ── Iniciar servidor ─────────────────────────
app.listen(PORT, () => {
  console.log('');
  console.log('╔═══════════════════════════════════════╗');
  console.log('║   RINNOVATI INSTITUTE — PAGOS BOLD    ║');
  console.log('╠═══════════════════════════════════════╣');
  console.log(`║  Servidor:  http://localhost:${PORT}       ║`);
  console.log(`║  Ambiente:  ${IS_SANDBOX ? 'SANDBOX (pruebas)    ' : 'PRODUCCIÓN          '}  ║`);
  console.log('╚═══════════════════════════════════════╝');
  console.log('');
});
