// ============================================
// RINNOVATI INSTITUTE — CONEXIÓN MySQL
// ============================================
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

// Configuración de la ruta del log
const LOG_PATH = '/home/rinnovat/log_db/error_db.txt';

const pool = mysql.createPool({
  host:     process.env.DB_HOST     || 'localhost',
  port:     parseInt(process.env.DB_PORT || '3306'),
  user:     process.env.DB_USER     || 'rinnovat_db',
  password: process.env.DB_PASSWORD || ';?$yiLK+8Bk(&uKG',
  database: process.env.DB_NAME     || 'rinnovat_rinnovati',
  waitForConnections: true,
  connectionLimit:    10,
  queueLimit:         0
});

// Función para escribir el error en el archivo
const logErrorToFile = (err) => {
  const timestamp = new Date().toISOString();
  const logMessage = `[${timestamp}] ERROR: ${err.message} | CODE: ${err.code} | ERRNO: ${err.errno}\n`;

  // AppendFile añade el error al final del archivo sin borrar lo anterior
  fs.appendFile(LOG_PATH, logMessage, (fsErr) => {
    if (fsErr) {
      console.error('❌ No se pudo escribir en el archivo de log:', fsErr);
    }
  });
};

// Verificar conexión al iniciar
pool.getConnection()
  .then(conn => {
    console.log('✅ Base de datos conectada correctamente');
    conn.release();
  })
  .catch(err => {
    console.error('❌ Error conectando a la base de datos:', err.message);
    // Guardamos la incidencia en tu carpeta de home
    logErrorToFile(err);
  });

module.exports = pool;