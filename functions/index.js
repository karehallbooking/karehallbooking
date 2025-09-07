const functions = require('firebase-functions');
const admin = require('firebase-admin');
const { sendEmail, ADMIN_EMAIL, PUBLIC_SITE_URL } = require('./mailer');
const path = require('path');
const dotenv = require('dotenv');
dotenv.config({ path: path.join(__dirname, '.env') });
console.log('✅ server.js env loaded');

// Initialize Admin SDK
if (!admin.apps.length) {
  try {
    const base64 = process.env.FIREBASE_CREDENTIALS_BASE64;
    if (base64) {
      const json = JSON.parse(Buffer.from(base64, 'base64').toString('utf8'));
      admin.initializeApp({ credential: admin.credential.cert(json) });
    } else {
      admin.initializeApp();
    }
  } catch (e) {
    console.error('Admin init error', e);
    admin.initializeApp();
  }
}

const db = admin.firestore();

async function logMail({ type, to, bookingId, status, error }) {
  try {
    await db.collection('mailLogs').add({
      type,
      to,
      bookingId: bookingId || null,
      status,
      error: error ? String(error) : null,
      createdAt: admin.firestore.FieldValue.serverTimestamp()
    });
  } catch (e) {
    console.error('mailLogs write failed', e);
  }
}

exports.onUserCreate = functions.firestore
  .document('users/{uid}')
  .onCreate(async (snap, context) => {
    const user = snap.data() || {};
    const email = user.email;
    const name = user.name || 'there';
    if (!email) return null;
    try {
      await sendEmail({
        to: email,
        subject: 'Welcome to KARE Hall Booking — Let\'s get started',
        templateName: 'welcome',
        data: { name }
      });
      await logMail({ type: 'welcome', to: email, status: 'sent' });
    } catch (err) {
      console.error('Welcome mail failed', err);
      await logMail({ type: 'welcome', to: email, status: 'failed', error: err });
    }
    return null;
  });

exports.onBookingCreate = functions.firestore
  .document('bookings/{bid}')
  .onCreate(async (snap, context) => {
    const booking = snap.data() || {};
    const bid = context.params.bid;
    console.log('📌 Booking request received');
    const userEmail = booking.email || booking.userEmail;
    const hallName = booking.hall || booking.hallName || 'Hall';
    const subjectUser = `We received your booking request — ${hallName}`;
    const subjectAdmin = `New booking request — ${hallName}`;

    const data = { bookingId: bid, hall: hallName, startDateTime: booking.startDateTime, endDateTime: booking.endDateTime, purpose: booking.purpose, peopleCount: booking.peopleCount, bookedBy: booking.bookedBy, contact: booking.contact, email: userEmail };

    try {
      console.log(`✅ Booking saved with ID: ${bid}`);
      if (userEmail) {
        try {
          await sendEmail({ to: userEmail, subject: subjectUser, templateName: 'booking_received_user', data });
          await logMail({ type: 'booking_received_user', to: userEmail, bookingId: bid, status: 'sent' });
        } catch (err) {
          console.error('❌ User booking mail failed:', err.message || err);
          await logMail({ type: 'booking_received_user', to: userEmail, bookingId: bid, status: 'failed', error: err });
        }
      }
      const adminEmail = ADMIN_EMAIL;
      if (adminEmail) {
        try {
          await sendEmail({ to: adminEmail, subject: subjectAdmin, templateName: 'booking_received_admin', data });
          await logMail({ type: 'booking_received_admin', to: adminEmail, bookingId: bid, status: 'sent' });
        } catch (err) {
          console.error('❌ Admin booking mail failed:', err.message || err);
          await logMail({ type: 'booking_received_admin', to: adminEmail, bookingId: bid, status: 'failed', error: err });
        }
      }
    } catch (outerErr) {
      console.error('❌ onBookingCreate error:', outerErr.message || outerErr);
    }
    return null;
  });

exports.onBookingUpdate = functions.firestore
  .document('bookings/{bid}')
  .onUpdate(async (change, context) => {
    const before = change.before.data() || {};
    const after = change.after.data() || {};
    const bid = context.params.bid;
    if (before.status === after.status) return null;
    const status = after.status;
    const userEmail = after.email || after.userEmail;
    const hallName = after.hall || after.hallName || 'Hall';
    const data = { bookingId: bid, hall: hallName, startDateTime: after.startDateTime, endDateTime: after.endDateTime, purpose: after.purpose, peopleCount: after.peopleCount, bookedBy: after.bookedBy, contact: after.contact, rejectionReason: after.rejectionReason };
    if (!userEmail) return null;
    try {
      if (status === 'approved') {
        await sendEmail({ to: userEmail, subject: `Your booking is approved ✅ — ${hallName}`, templateName: 'booking_approved', data });
        await logMail({ type: 'booking_approved', to: userEmail, bookingId: bid, status: 'sent' });
      } else if (status === 'rejected') {
        await sendEmail({ to: userEmail, subject: `Update on your booking request — ${hallName}`, templateName: 'booking_rejected', data });
        await logMail({ type: 'booking_rejected', to: userEmail, bookingId: bid, status: 'sent' });
      }
    } catch (err) {
      console.error(`❌ Booking status mail failed: ${err && err.message ? err.message : err}`);
      await logMail({ type: `booking_${status}`, to: userEmail, bookingId: bid, status: 'failed', error: err });
    }
    return null;
  });

// Optional HTTPS endpoint to create a booking (for testing / frontend demo)
exports.createBooking = functions.https.onRequest(async (req, res) => {
  if (req.method !== 'POST') return res.status(405).send('Method Not Allowed');
  try {
    console.log('📌 Booking request received (HTTP)');
    const body = typeof req.body === 'string' ? JSON.parse(req.body) : req.body;
    const doc = await db.collection('bookings').add({
      hall: body.hall,
      startDateTime: body.startDateTime,
      endDateTime: body.endDateTime,
      purpose: body.purpose,
      peopleCount: body.peopleCount,
      bookedBy: body.bookedBy,
      contact: body.contact,
      email: body.email,
      status: 'pending',
      createdAt: admin.firestore.FieldValue.serverTimestamp()
    });
    console.log(`✅ Booking saved with ID: ${doc.id}`);
    return res.json({ id: doc.id });
  } catch (e) {
    console.error('❌ createBooking error', e.message || e);
    return res.status(500).json({ error: 'Failed to create booking' });
  }
});


