import { db, collections } from '../config/firebase';
import admin from 'firebase-admin';
import { sendMail } from '../utils/mailer';
import { bookingReceivedUser, bookingReceivedAdmin } from '../utils/emailTemplates';

function getString(field: any): string | undefined {
  if (field === undefined || field === null) return undefined;
  return String(field);
}

export function startBookingWatcher(): void {
  const serverStartedAtMs = Date.now();
  const startIso = new Date(serverStartedAtMs).toISOString();
  console.log(`👀 Booking watcher started at ${startIso}`);
  const lastStatus = new Map<string, string | undefined>();

  function toMs(value: any): number | null {
    if (!value) return null;
    try {
      if (typeof value === 'string') return Date.parse(value);
      if (typeof value.toDate === 'function') return value.toDate().getTime();
      if (value instanceof Date) return value.getTime();
      return Date.parse(String(value));
    } catch {
      return null;
    }
  }

  // Single listener for both additions and modifications
  db.collection(collections.bookings)
    .onSnapshot((snapshot) => {
      snapshot.docChanges().forEach(async (change) => {
        const doc = change.doc;
        const b = doc.data() || {};
        const bookingId = doc.id;
        
        if (change.type === 'added') {
          // Handle new bookings
          const createdAtMs = toMs((b as any).createdAt);
          if (createdAtMs !== null) {
            const thresholdMs = serverStartedAtMs - 2000; // small clock skew tolerance
            if (createdAtMs < thresholdMs) {
              // Do not process old records
              return;
            }
          }
          try {
            console.log('📌 Booking request received');
            console.log(`✅ Booking saved with ID: ${bookingId}`);

            const hallName = getString(b.hallName) || getString(b.hall) || 'Hall';
            const userEmail = getString(b.userEmail) || getString(b.email);
            const emailData = {
              bookingId,
              hallName,
              dates: (b.dates || []) as string[],
              timeFrom: getString(b.timeFrom) || '',
              timeTo: getString(b.timeTo) || '',
              purpose: getString(b.purpose) || '',
              peopleCount: b.seatingCapacity as number | undefined,
              bookedBy: getString(b.userName),
              contact: getString(b.userMobile),
              userEmail: userEmail || ''
            };

            if (userEmail) {
              try {
                await sendMail({
                  to: userEmail,
                  subject: `We received your booking request — ${hallName}`,
                  html: bookingReceivedUser(emailData),
                  text: `We received your booking request for ${hallName}. View: https://karehallbooking.netlify.app/my-bookings/${bookingId}`
                });
              } catch (e: any) {
                console.error('❌ User booking mail failed:', e?.message || e);
              }
            }

            if (process.env.ADMIN_EMAIL) {
              try {
                await sendMail({
                  to: process.env.ADMIN_EMAIL!,
                  subject: `New booking request — ${hallName}`,
                  html: bookingReceivedAdmin(emailData),
                  text: `New booking ID: ${bookingId}`
                });
              } catch (e: any) {
                console.error('❌ Admin booking mail failed:', e?.message || e);
              }
            }
            lastStatus.set(bookingId, getString(b.status));
          } catch (err: any) {
            console.error('❌ Booking watcher create error:', err?.message || err);
          }
        } else if (change.type === 'modified') {
          // Handle status changes - just log for monitoring, emails are sent directly by admin routes
          const prev = lastStatus.get(bookingId);
          const current = getString(b.status);
          console.log(`🔄 Booking ${bookingId} status changed: ${prev} → ${current}`);
          lastStatus.set(bookingId, current);
          
          // Note: Status change emails are now handled directly in admin routes
          // to avoid duplicate emails and Firestore listener reliability issues on Render
          if (current === 'approved' || current === 'rejected') {
            console.log(`📧 Status change email should be sent by admin route for booking ${bookingId}`);
          }
        }
      });
    }, (err) => {
      console.error('❌ Booking watcher stream error:', err.message || err);
    });
}