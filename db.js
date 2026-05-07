// ============================================
// RINNOVATI INSTITUTE — CONEXIÓN MySQL
// ============================================
const mysql = require('mysql2/promise');

const pool = mysql.createPool({
  host:     process.env.DB_HOST     || 'localhost',
  port:     parseInt(process.env.DB_PORT || '3306'),
  user:     process.env.DB_USER     || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME     || 'rinnovati',
  waitForConnections: true,
  connectionLimit:    10,
  queueLimit:         0
});

// Verificar conexión al iniciar
pool.getConnection()
  .then(conn => {
    console.log('✅ Base de datos conectada correctamente');
    conn.release();
  })
  .catch(err => {
    console.error('❌ Error conectando a la base de datos:', err.message);
  });

module.exports = pool;
