const colors = {
  dark: '#154D71',
  mid: '#1C6EA4',
  accent: '#33A1E0'
};

function layout(title: string, body: string): string {
  return `
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
.container{max-width:600px;margin:0 auto;background:#ffffff;color:#1a1a1a;font-family:Arial,Helvetica,sans-serif;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb}
.header{background:${colors.dark};color:#ffffff;padding:20px;text-align:center;font-weight:bold}
.content{padding:20px}
.h1{font-size:20px;margin:0 0 10px 0;color:${colors.dark}}
.table{width:100%;border-collapse:collapse;margin:14px 0;border:1px solid #e5e7eb}
.table th,.table td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left}
.table th{background:#f1f5f9;color:${colors.dark}}
.cta{display:inline-block;background:${colors.mid};color:#ffffff !important;text-decoration:none;padding:12px 18px;border-radius:6px;font-weight:bold}
.cta:hover{background:#175e8d}
.footer{padding:14px 20px;text-align:center;background:#f8fafc;color:#6b7280;font-size:12px}
</style></head>
<body style="margin:0;background:#f1f5f9">
  <div class="container">
    <div class="header">KARE Hall Booking</div>
    <div class="content">
      <h1 class="h1">${title}</h1>
      ${body}
    </div>
    <div class="footer">Questions? Contact ${process.env.ADMIN_EMAIL || ''}</div>
  </div>
</body></html>`;
}

export function bookingReceivedUser(data: {
  bookingId: string; hallName: string; dates: string[]; timeFrom: string; timeTo: string; purpose: string; peopleCount?: number; bookedBy?: string; contact?: string;
}): string {
  const site = 'https://karehallbooking.netlify.app';
  const rows = `
<tr><th>Hall</th><td>${data.hallName}</td></tr>
<tr><th>Dates</th><td>${(data.dates || []).join(', ')}</td></tr>
<tr><th>Time</th><td>${data.timeFrom} - ${data.timeTo}</td></tr>
<tr><th>Purpose</th><td>${data.purpose}</td></tr>
${data.peopleCount ? `<tr><th>Attendees</th><td>${data.peopleCount}</td></tr>` : ''}
${data.bookedBy ? `<tr><th>Booked By</th><td>${data.bookedBy}</td></tr>` : ''}
${data.contact ? `<tr><th>Contact</th><td>${data.contact}</td></tr>` : ''}
`;
  const body = `
<p>We received your request. Admin will confirm within a few hours.</p>
<table class="table">${rows}</table>
<a class="cta" href="${site}/my-bookings/${data.bookingId}" target="_blank">View Booking</a>`;
  return layout('Booking Request Received', body);
}

export function bookingReceivedAdmin(data: {
  bookingId: string; hallName: string; dates: string[]; timeFrom: string; timeTo: string; purpose: string; userEmail: string; peopleCount?: number; bookedBy?: string; contact?: string;
}): string {
  const site = 'https://karehallbooking.netlify.app';
  const rows = `
<tr><th>Hall</th><td>${data.hallName}</td></tr>
<tr><th>Dates</th><td>${(data.dates || []).join(', ')}</td></tr>
<tr><th>Time</th><td>${data.timeFrom} - ${data.timeTo}</td></tr>
<tr><th>Purpose</th><td>${data.purpose}</td></tr>
${data.peopleCount ? `<tr><th>Attendees</th><td>${data.peopleCount}</td></tr>` : ''}
${data.bookedBy ? `<tr><th>Booked By</th><td>${data.bookedBy}</td></tr>` : ''}
${data.contact ? `<tr><th>Contact</th><td>${data.contact}</td></tr>` : ''}
<tr><th>User Email</th><td>${data.userEmail}</td></tr>`;
  const body = `
<table class="table">${rows}</table>
<a class="cta" href="${site}/admin/bookings/${data.bookingId}/approve" target="_blank">Approve Booking</a>`;
  return layout(`New Booking Request: ${data.hallName}`, body);
}

export function bookingApproved(data: {
  bookingId: string; hallName: string; dates: string[]; timeFrom: string; timeTo: string; purpose: string; peopleCount?: number; bookedBy?: string; contact?: string;
}): string {
  const site = 'https://karehallbooking.netlify.app';
  const rows = `
<tr><th>Hall</th><td>${data.hallName}</td></tr>
<tr><th>Dates</th><td>${(data.dates || []).join(', ')}</td></tr>
<tr><th>Time</th><td>${data.timeFrom} - ${data.timeTo}</td></tr>
<tr><th>Purpose</th><td>${data.purpose}</td></tr>
${data.peopleCount ? `<tr><th>Attendees</th><td>${data.peopleCount}</td></tr>` : ''}
${data.bookedBy ? `<tr><th>Booked By</th><td>${data.bookedBy}</td></tr>` : ''}
${data.contact ? `<tr><th>Contact</th><td>${data.contact}</td></tr>` : ''}
`;
  const body = `
<p>You can now conduct your event. If you need anything, contact admin.</p>
<table class="table">${rows}</table>
<a class="cta" href="${site}/my-bookings/${data.bookingId}" target="_blank">View Booking Details</a>`;
  return layout('Your booking is approved ✅', body);
}

export function bookingRejected(data: {
  bookingId: string; hallName: string; dates: string[]; timeFrom: string; timeTo: string; purpose: string; rejectionReason?: string;
}): string {
  const site = 'https://karehallbooking.netlify.app';
  const rows = `
<tr><th>Hall</th><td>${data.hallName}</td></tr>
<tr><th>Dates</th><td>${(data.dates || []).join(', ')}</td></tr>
<tr><th>Time</th><td>${data.timeFrom} - ${data.timeTo}</td></tr>
<tr><th>Purpose</th><td>${data.purpose}</td></tr>`;
  const body = `
<p>We're sorry to inform you that your booking request could not be accommodated.</p>
${data.rejectionReason ? `<p><strong>Reason:</strong> ${data.rejectionReason}</p>` : ''}
<table class="table">${rows}</table>
<a class="cta" href="${site}/book" target="_blank">Try another hall</a>`;
  return layout('Booking Request Update', body);
}

export function welcomeEmail(data: { name: string }): string {
  const site = 'https://karehallbooking.netlify.app';
  const body = `
<p>Hi ${data.name}, we're excited to have you on board. Booking halls is now simple and fast.</p>
<div class="spacer"></div>
<ol class="steps">
  <li>Create a booking with your event details.</li>
  <li>Track the status from your dashboard.</li>
  <li>Conduct your event with confidence.</li>
</ol>
<div class="spacer"></div>
<a class="cta" href="${site}/book" target="_blank">Book a Hall</a>`;
  return layout('Welcome to KARE Hall Booking', body);
}


