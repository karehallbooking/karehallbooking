const nodemailer = require('nodemailer');

const SMTP_HOST = process.env.SMTP_HOST;
const SMTP_PORT = Number(process.env.SMTP_PORT || 465);
const SMTP_USER = process.env.SMTP_USER;
const SMTP_PASS = process.env.SMTP_PASS;

const transport = nodemailer.createTransport({
  host: SMTP_HOST,
  port: SMTP_PORT,
  secure: SMTP_PORT === 465,
  auth: { user: SMTP_USER, pass: SMTP_PASS }
});

async function sendMail({ to, subject, html, text }) {
  if (!to) throw new Error('Missing recipient');
  try {
    console.log(`📨 Sending mail → to=${to} subject="${subject}"`);
    const info = await transport.sendMail({ from: `KARE Hall Booking <${SMTP_USER}>`, to, subject, html, text });
    console.log(`📧 Mail sent to ${to} (messageId=${info.messageId || 'n/a'})`);
    return info;
  } catch (err) {
    console.error(`❌ Mail failed to ${to}: ${err && err.message ? err.message : err}`);
    throw err;
  }
}

module.exports = { sendMail };


