# 🏥 Rinnovati Institute — Sistema de Pagos Bold

## Estructura del proyecto

```
rinnovati-bold/
├── server.js          ← Backend Node.js (servidor + API Bold)
├── .env               ← Variables de entorno (llaves, precios)
├── package.json
└── public/
    └── index.html     ← Landing page con modal de pago integrado
```

---

## 🚀 Cómo correr el proyecto

### 1. Instalar dependencias
```bash
npm install
```

### 2. Configurar variables de entorno
Abre el archivo `.env` y ajusta:
```env
BOLD_SECRET_KEY=BWsvMdbVoscm9JgURse6Vw     # Llave secreta Bold (pruebas)
BOLD_PUBLIC_KEY=09-yxM_hgo6r5dNEPe44zx5XIe6kN5bfWJydj2V8O9Q  # Llave pública Bold
BOLD_ENV=sandbox                            # 'sandbox' o 'production'
PORT=3000
```

### 3. Ajustar precios (en server.js línea ~50)
```js
const PRECIOS = {
  COP: 1_250_000,   // ← Precio en pesos colombianos
  USD: 297          // ← Precio en dólares
};
```

### 4. Iniciar el servidor
```bash
node server.js
```

### 5. Abrir en el navegador
```
http://localhost:3000
```

---

## 💳 Cómo funciona el flujo de pago

```
Usuario elige fecha → Abre modal → Llena datos
        ↓
POST /api/create-payment  (backend genera hash de integridad)
        ↓
Frontend carga widget Bold con hash firmado
        ↓
Usuario paga con tarjeta / PSE / Nequi en ventana Bold
        ↓
Bold llama webhook POST /api/bold-webhook (confirma pago)
        ↓
Usuario ve pantalla de éxito
```

---

## 🔒 Seguridad

| Elemento | Ubicación | Por qué |
|---|---|---|
| `BOLD_SECRET_KEY` | Solo en `.env` del servidor | Nunca en el frontend |
| Hash de integridad | Generado en `server.js` | Firmado con secret key |
| Verificación webhook | `server.js` con HMAC-SHA256 | Evita pagos falsos |

---

## 🔄 Pasar a producción

1. En tu panel Bold → obtén las llaves de **producción**
2. Cambia en `.env`:
   ```env
   BOLD_SECRET_KEY=TU_LLAVE_SECRETA_PRODUCCION
   BOLD_PUBLIC_KEY=TU_LLAVE_PUBLICA_PRODUCCION
   BOLD_ENV=production
   ```
3. Configura el **webhook** en tu panel Bold → URL: `https://tudominio.com/api/bold-webhook`
4. Despliega en Railway, Render, o VPS

---

## 📬 Configurar notificaciones de pago

En `server.js`, en la sección `transaction.approved` del webhook, 
puedes agregar envío de correo con nodemailer:

```js
if (type === 'transaction.approved') {
  // Enviar email de confirmación al médico
  // Registrar en base de datos
  // Enviar WhatsApp via Twilio
}
```

---

## 🧪 Tarjetas de prueba Bold (Sandbox)

| Tarjeta | Número | Resultado |
|---|---|---|
| Visa aprobada | 4111 1111 1111 1111 | ✅ Aprobado |
| Mastercard aprobada | 5500 0000 0000 0004 | ✅ Aprobado |
| Rechazada | 4000 0000 0000 0002 | ❌ Rechazado |

CVV: cualquier 3 dígitos | Fecha: cualquier fecha futura
