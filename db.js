// ============================================
// RINNOVATI INSTITUTE — CONEXIÓN FIREBASE
// ============================================
const admin = require('firebase-admin');

const app = admin.initializeApp({
  credential: admin.credential.cert({
    projectId:   process.env.FIREBASE_PROJECT_ID,
    clientEmail: process.env.FIREBASE_CLIENT_EMAIL,
    privateKey:  process.env.FIREBASE_PRIVATE_KEY?.replace(/\\n/g, '\n')
  })
});

const db = admin.firestore(app);

console.log('✅ Firebase Firestore conectado correctamente');

module.exports = db;
