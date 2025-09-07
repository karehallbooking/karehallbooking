const nodemailer = require('nodemailer');
const fs = require('fs');
const path = require('path');
const mustache = require('mustache');
require('dotenv').config({ path: path.join(__dirname, '.env') });

const SMTP_HOST = process.env.SMTP_HOST;
const SMTP_PORT = Number(process.env.SMTP_PORT || 465);
const SMTP_USER = process.env.SMTP_USER;
const SMTP_PASS = process.env.SMTP_PASS;
const ADMIN_EMAIL = process.env.ADMIN_EMAIL;
const PUBLIC_SITE_URL = process.env.PUBLIC_SITE_URL || 'https://example.com';

const transport = nodemailer.createTransport({
  host: SMTP_HOST,
  port: SMTP_PORT,
  secure: SMTP_PORT === 465,
  auth: { user: SMTP_USER, pass: SMTP_PASS }
});

function validateEnv() {
  const missing = [];
  if (!SMTP_HOST) missing.push('SMTP_HOST');
  if (!SMTP_PORT) missing.push('SMTP_PORT');
  if (!SMTP_USER) missing.push('SMTP_USER');
  if (!SMTP_PASS) missing.push('SMTP_PASS');
  if (!ADMIN_EMAIL) missing.push('ADMIN_EMAIL');
  if (!PUBLIC_SITE_URL) missing.push('PUBLIC_SITE_URL');
  if (missing.length) {
    console.warn(`⚠️  Missing environment variables: ${missing.join(', ')}`);
  } else {
    console.log('✅ Mailer env loaded');
  }
}
validateEnv();

function loadTemplate(fileName) {
  const filePath = path.join(__dirname, 'templates', fileName);
  return fs.readFileSync(filePath, 'utf8');
}

function renderTemplate(templateName, data) {
  const html = loadTemplate(`${templateName}.html`);
  const textPath = path.join(__dirname, 'templates', `${templateName}.txt`);
  const text = fs.existsSync(textPath) ? fs.readFileSync(textPath, 'utf8') : '';
  const mergedData = { ADMIN_EMAIL, PUBLIC_SITE_URL, ...data };
  return {
    html: mustache.render(html, mergedData),
    text: mustache.render(text, mergedData)
  };
}

async function sendEmail({ to, subject, templateName, data }) {
  if (!to) throw new Error('Missing recipient');
  const { html, text } = renderTemplate(templateName, data || {});
  try {
    console.log(`📨 Sending mail → to=${to} subject="${subject}" template=${templateName}`);
    const info = await transport.sendMail({
      from: `KARE Hall Booking <${SMTP_USER}>`,
      to,
      subject,
      text,
      html
    });
    console.log(`📧 Mail sent to ${to} (messageId=${info.messageId || 'n/a'})`);
    return info;
  } catch (err) {
    console.error(`❌ Mail failed to ${to}: ${err && err.message ? err.message : err}`);
    throw err;
  }
}

module.exports = { sendEmail, renderTemplate, ADMIN_EMAIL, PUBLIC_SITE_URL };


