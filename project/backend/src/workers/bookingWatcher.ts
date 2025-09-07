import { db, collections } from '../config/firebase';
import admin from 'firebase-admin';
import { sendMail } from '../utils/mailer';
import { bookingReceivedUser, bookingReceivedAdmin, bookingApproved, bookingRejected } from '../utils/emailTemplates';

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
                  text: `We received your booking request for ${hallName}. View: ${(process.env.PUBLIC_SITE_URL || '')}/my-bookings/${bookingId}`
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
          // Handle status changes
          const prev = lastStatus.get(bookingId);
          const current = getString(b.status);
          lastStatus.set(bookingId, current);
          if (prev === current) return;
          if (current !== 'approved' && current !== 'rejected') return;
          const userEmail = getString(b.userEmail) || getString(b.email);
          if (!userEmail) return;
          
          // Also ensure the update happened after server start (avoid replay on initial snapshot)
          const updatedAtMs = toMs((b as any).updatedAt) || toMs((b as any).createdAt);
          if (updatedAtMs !== null) {
            const thresholdMs = serverStartedAtMs - 2000;
            if (updatedAtMs < thresholdMs) {
              return;
            }
          }
          try {
            const hallName = getString(b.hallName) || getString(b.hall) || 'Hall';
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
              rejectionReason: getString(b.adminComments)
            } as any;
            if (current === 'approved') {
              await sendMail({
                to: userEmail,
                subject: `Your booking is approved ✅ — ${hallName}`,
                html: bookingApproved(emailData),
                text: `Your booking is approved. View: ${(process.env.PUBLIC_SITE_URL || '')}/my-bookings/${bookingId}`
              });
            } else {
              await sendMail({
                to: userEmail,
                subject: `Update on your booking request — ${hallName}`,
                html: bookingRejected(emailData),
                text: `Your booking was not approved.${emailData.rejectionReason ? ' Reason: ' + emailData.rejectionReason : ''} Try again: ${(process.env.PUBLIC_SITE_URL || '')}/book`
              });
            }
            console.log(`✅ Booking ${bookingId} status email sent (${current})`);
          } catch (err: any) {
            console.error('❌ Booking watcher status error:', err?.message || err);
          }
        }
      });
    }, (err) => {
      console.error('❌ Booking watcher stream error:', err.message || err);
    });
}