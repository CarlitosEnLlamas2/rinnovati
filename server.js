// ============================================
// RINNOVATI INSTITUTE — SERVIDOR DE PAGOS BOLD
// ============================================
require('dotenv').config();
const express = require('express');
const cors = require('cors');
const crypto = require('crypto');
const path = require('path');
const db = require('./db');


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

// ── Config Gemini ─────────────────────────────
const GEMINI_API_KEY = process.env.GEMINI_API_KEY;
const GEMINI_MODEL   = 'gemini-2.0-flash';
const GEMINI_BASE    = `https://generativelanguage.googleapis.com/v1beta/models/${GEMINI_MODEL}:generateContent`;

const GEMINI_SYSTEM_PROMPT = `Eres la asistente virtual de Rinnovati Institute, especializada en la Masterclass Técnica PDRN de Salmón. Responde siempre en español, de forma profesional y cálida. Tu objetivo es resolver dudas de médicos y profesionales de la salud sobre el programa y orientarlos hacia la inscripción.

SOBRE EL PROGRAMA:
- Nombre: Masterclass Técnica PDRN de Salmón
- Empresa: Rinnovati Institute S.A.S. — Medellín, Colombia
- Sitio web: www.rinnovatinstitute.com
- Formato: Sesión privada online de 3 horas, 100% en vivo.
- No hay grabaciones — la filosofía se basa en el debate en vivo y la interacción real

¿QUÉ ES PDRN?
Los polinucleótidos (PDRN) son moléculas activas que reparan el tejido desde el ADN. Se usan en medicina regenerativa y tratamientos estéticos de alto ticket. La sesión cubre su farmacodinámica completa: cómo actúa, argumentos científicos para justificar resultados superiores ante los pacientes.

PRECIO:
- USD 97 (precio de lanzamiento, fase piloto)
- Precio regular: USD 120
- También disponible en pesos colombianos: lo equivalente en pesos segun la tasa del dia.

QUÉ INCLUYE:
1. Sesión online privada de 3 horas 100% en vivo con la especialista
2. Mapas de Aplicación Médica (rostro, zona periorbitaria, cuello) — protocolos visuales listos para sala
3. Algoritmos de Dosificación por crono envejecimiento del paciente — sin margen para improvisación
4. Certificación oficial de Rinnovati Institute S.A.S. con validez legal y académica
5. Acceso a debate clínico en tiempo real con la especialista — resuelva casos reales de su práctica
6. Validación de cédula profesional para garantizar el rigor médico de la sala

FECHAS DISPONIBLES (ciclo actual):
- Mayo 17, 2025 — 2 cupos disponibles
- Mayo 24, 2025 — 4 cupos disponibles
- Junio 5, 2025 — 4 cupos disponibles
Solo 4 fechas al mes para garantizar calidad de mentoría personalizada.

PARA QUIÉN ES:
- Médicos y profesionales de la salud certificados
- Aplica tanto si ya trabaja con polinucleótidos como si parte desde cero
- Se requiere validación de cédula profesional

LO QUE NO ES:
- No es un webinar masivo — sala pequeña para profundidad real
- No es contenido pregrabado — cada sesión es única según los participantes
- No es para cualquier perfil — filtro estricto por cédula profesional

PREGUNTAS FRECUENTES:
P: ¿Necesito experiencia previa con PDRN?
R: No. Está diseñado tanto para quienes parten desde cero como para quienes ya trabajan con polinucleótidos.

P: ¿Cómo se valida mi cédula profesional?
R: Tras el pago, recibirá un formulario para adjuntar su documento de identificación profesional. El equipo lo valida en máximo 24 horas hábiles.

P: ¿La sesión queda grabada?
R: No. La filosofía es la interacción en vivo. No se comparten grabaciones para proteger la privacidad del debate clínico.

P: ¿Cuándo recibo la certificación?
R: La certificación digital se envía en máximo 5 días hábiles posteriores a la sesión.

P: ¿Puedo cambiar mi fecha?
R: Sí, con al menos 48 horas de anticipación, sujeto a disponibilidad en otras fechas del ciclo actual.

CÓMO INSCRIBIRSE:
El usuario selecciona una fecha → completa el formulario (nombre, email, teléfono, cédula) → procesa el pago → recibe confirmación. Si alguien quiere inscribirse, indícale que haga clic en "Ver Fechas y Postular" o "Asegurar mi Acceso Técnico" en la página.

Sé concisa. Usa bullet points cuando ayude a la claridad. No inventes información que no esté aquí. Si preguntan algo que no sabes, sugiere contactar directamente a www.rinnovatinstitute.com.`;

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
app.post('/api/create-payment', async (req, res) => {
  try {
    const {
      nombre, email, telefono, moneda = 'COP',
      pais, ciudad,
      especialidad, institucion, fecha, fuente,
      firma, fechaFirma,
      certificacion = {}
    } = req.body;

    // Validaciones básicas
    if (!nombre || !email || !fecha) {
      return res.status(400).json({
        ok: false,
        error: 'Faltan campos requeridos: nombre, email, fecha'
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

    // ── Guardar TODOS los campos del formulario en Firestore ──
    const certTs = certificacion.timestamp ? new Date(certificacion.timestamp) : new Date();
    await db.collection('registros').doc(orderId).set({
      order_id:   orderId,
      moneda:     monedaUpper,
      monto:      amount,
      estado:     'pendiente',
      nombre,
      email,
      telefono:             telefono    || null,
      cedula:               null,
      pais:                 pais        || null,
      ciudad:               ciudad      || null,
      especialidad:         especialidad || null,
      registro_medico:      null,
      institucion:          institucion || null,
      fecha_sesion:         fecha,
      fuente:               fuente      || null,
      firma:                firma       || null,
      fecha_firma:          fechaFirma  || null,
      cert_medico_titulado:      certificacion.esMedico           ? true : false,
      cert_habilitacion_vigente: certificacion.habilitacionVigente ? true : false,
      cert_datos_veridicos:      certificacion.datosVeridicos      ? true : false,
      cert_acepta_terminos:      certificacion.aceptaTerminos      ? true : false,
      cert_timestamp:            certTs,
      creado_en:                 new Date()
    });

    console.log(`[PAGO] Nueva orden: ${orderId} | ${nombre} | ${fecha} | ${amount} ${monedaUpper}`);

    res.json({
      ok: true,
      orderId,
      amount,
      currency: monedaUpper,
      integrityHash: hash,
      publicKey: BOLD_PUBLIC_KEY,
      description: `Masterclass PDRN — ${fecha}`,
      customerEmail: email,
      customerName: nombre,
      sandbox: IS_SANDBOX
    });

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
app.post('/api/bold-webhook', express.raw({ type: 'application/json' }), async (req, res) => {
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
      const { order_id, amount, currency, customer, id: boldTxId } = data;
      console.log(`✅ PAGO APROBADO: ${order_id} | ${amount} ${currency} | ${customer?.email}`);

      await db.collection('registros').doc(order_id).update({
        estado:    'aprobado',
        bold_tx_id: boldTxId || null
      });
    }

    if (type === 'transaction.rejected') {
      const { order_id, reason, id: boldTxId } = data;
      console.log(`❌ PAGO RECHAZADO: ${order_id} | Razón: ${reason}`);

      await db.collection('registros').doc(order_id).update({
        estado:         'rechazado',
        razon_rechazo:  reason    || null,
        bold_tx_id:     boldTxId  || null
      });
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

// ============================================
// CHAT IA — GEMINI
// ============================================

// POST /api/ai-chat  — visitante envía mensaje con historial
app.post('/api/ai-chat', async (req, res) => {
  const { history = [], message } = req.body;
  
  // 1. Validaciones iniciales
  if (!message) return res.status(400).json({ ok: false, error: 'Falta el mensaje' });
  if (!GEMINI_API_KEY) return res.status(503).json({ ok: false, error: 'API Key no configurada' });

  try {
    const axios = require('axios');

    // 2. Limpiar y validar historial (Debe alternar user/model y empezar por user)
    let formattedHistory = history
      .map(m => ({
        role: m.role === 'assistant' ? 'model' : 'user',
        parts: [{ text: m.text }]
      }))
      .filter(m => m.parts[0].text); // Evitar textos vacíos

    // Asegurar que el primer mensaje sea 'user'
    if (formattedHistory.length > 0 && formattedHistory[0].role === 'model') {
      formattedHistory.shift(); 
    }

    // 3. Construir el payload final
    const payload = {
      system_instruction: { 
        parts: [{ text: GEMINI_SYSTEM_PROMPT }] 
      },
      contents: [
        ...formattedHistory,
        { role: 'user', parts: [{ text: message }] }
      ],
      generationConfig: {
        temperature: 0.7,
        maxOutputTokens: 800, // Un poco más de margen para respuestas ricas
      }
    };

    const geminiRes = await axios.post(
      `${GEMINI_BASE}?key=${GEMINI_API_KEY}`,
      payload,
      { headers: { 'Content-Type': 'application/json' }, timeout: 25000 }
    );

    // 4. Extraer respuesta con seguridad
    const candidate = geminiRes.data?.candidates?.[0];
    
    // Bloqueo por seguridad (Safety)
    if (candidate?.finishReason === 'SAFETY') {
        return res.json({ ok: true, reply: "Lo siento, no puedo responder a eso por políticas de seguridad." });
    }

    const reply = candidate?.content?.parts?.[0]?.text || 'No recibí respuesta del modelo.';

    res.json({ ok: true, reply });

  } catch (err) {
    // IMPORTANTE: Imprime el error detallado de Google en tu consola para saber qué pasó
    console.error('[GEMINI ERROR]:', JSON.stringify(err.response?.data, null, 2) || err.message);
    
    const errorMsg = err.response?.data?.error?.message || 'Error al consultar la IA';
    res.status(500).json({ ok: false, error: errorMsg });
  }
});

// ── Error handler global (Express 5) ─────────
app.use((err, req, res, next) => {
  console.error('[SERVER ERROR]', err.message);
  res.status(500).json({ ok: false, error: 'Error interno del servidor' });
});

// ── Iniciar servidor ─────────────────────────
app.listen(PORT, () => {
  const hostDisplay = process.env.PORT ? 'Puerto del Servidor' : `http://localhost:${PORT}`;
  
  console.log('');
  console.log('╔═══════════════════════════════════════╗');
  console.log('║   RINNOVATI INSTITUTE — PAGOS BOLD    ║');
  console.log('╠═══════════════════════════════════════╣');
  console.log(`║  Servidor:  ${hostDisplay.padEnd(25)} ║`);
  console.log(`║  Ambiente:  ${(IS_SANDBOX ? 'SANDBOX' : 'PRODUCCIÓN').padEnd(25)} ║`);
  console.log('╚═══════════════════════════════════════╝');
  console.log('');
});
