const express = require('express');
const morgan = require('morgan');
const path = require('path');
const dotenv = require('dotenv');
dotenv.config({ path: path.join(__dirname, '..', '.env') });

// Validate essential envs early
const requiredEnvs = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'ADMIN_EMAIL'];
const missing = requiredEnvs.filter((k) => !process.env[k]);
if (missing.length) {
  console.warn(`⚠️  Missing environment variables: ${missing.join(', ')}`);
}
console.log('✅ server.js env loaded');

const admin = require('firebase-admin');
try {
  const base64 = process.env.FIREBASE_CREDENTIALS_BASE64;
  if (base64) {
    const json = JSON.parse(Buffer.from(base64, 'base64').toString('utf8'));
    admin.initializeApp({ credential: admin.credential.cert(json) });
  } else {
    admin.initializeApp();
  }
  console.log('✅ Firebase Admin initialized');
} catch (e) {
  console.error('❌ Firebase Admin init error:', e.message || e);
}

const app = express();
app.use(morgan('dev'));
app.use(express.json());

const bookingsRouter = require('./routes/bookings');
app.use('/api/bookings', bookingsRouter);

app.get('/', (_req, res) => res.status(200).send('KARE Hall Booking API'));

const PORT = process.env.PORT || 4000;
app.listen(PORT, () => {
  console.log(`🚀 Server listening on http://localhost:${PORT}`);
});

module.exports = app;


