import nodemailer from 'nodemailer';

const { SMTP_HOST, SMTP_PORT = '465', SMTP_USER, SMTP_PASS } = process.env as Record<string, string | undefined>;

const transport = nodemailer.createTransport({
  host: SMTP_HOST,
  port: Number(SMTP_PORT),
  secure: Number(SMTP_PORT) === 465,
  auth: { user: SMTP_USER, pass: SMTP_PASS }
});

export async function verifySmtp(): Promise<void> {
  try {
    await transport.verify();
    console.log('✅ SMTP connection verified');
  } catch (err: any) {
    console.error(`❌ SMTP verify failed: ${err?.message || err}`);
  }
}

export async function sendMail(params: { to: string; subject: string; html?: string; text?: string }): Promise<void> {
  const { to, subject, html, text } = params;
  if (!to) throw new Error('Missing recipient');
  try {
    console.log(`📨 Sending mail → to=${to} subject="${subject}"`);
    const info = await transport.sendMail({ from: `KARE Hall Booking <${SMTP_USER}>`, to, subject, html, text });
    console.log(`📧 Mail sent to ${to} (messageId=${(info as any).messageId || 'n/a'})`);
  } catch (err: any) {
    console.error(`❌ Mail failed to ${to}: ${err?.message || err}`);
    throw err;
  }
}


