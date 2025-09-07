const { Router } = require('express');
const admin = require('firebase-admin');
const { sendMail } = require('../utils/mailer');

const router = Router();
const db = admin.firestore();

router.post('/', async (req, res) => {
  console.log('📌 Booking request received (Express)');
  try {
    const body = req.body || {};
    const booking = {
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
    };
    const docRef = await db.collection('bookings').add(booking);
    console.log(`✅ Booking saved with ID: ${docRef.id}`);

    // Send user acknowledgement
    if (booking.email) {
      try {
        await sendMail({
          to: booking.email,
          subject: `We received your booking request — ${booking.hall}`,
          text: `Your booking request was received. View: ${process.env.PUBLIC_SITE_URL || ''}/my-bookings/${docRef.id}`,
          html: `<p>Your booking request was received.</p><p><a href="${process.env.PUBLIC_SITE_URL || ''}/my-bookings/${docRef.id}">View Booking</a></p>`
        });
      } catch (err) {
        console.error('❌ Failed to send user mail:', err.message || err);
      }
    }

    // Send admin notification
    if (process.env.ADMIN_EMAIL) {
      try {
        await sendMail({
          to: process.env.ADMIN_EMAIL,
          subject: `New booking request — ${booking.hall}`,
          text: `New booking. ID: ${docRef.id}`,
          html: `<p>New booking created.</p><p>ID: ${docRef.id}</p>`
        });
      } catch (err) {
        console.error('❌ Failed to send admin mail:', err.message || err);
      }
    }

    return res.status(201).json({ id: docRef.id });
  } catch (e) {
    console.error('❌ Booking create error:', e.message || e);
    return res.status(500).json({ error: 'Failed to create booking' });
  }
});

module.exports = router;


