import React, { useState } from 'react';

export default function BookingForm() {
  const [form, setForm] = useState({ hall: '', startDateTime: '', endDateTime: '', purpose: '', peopleCount: 1, bookedBy: '', contact: '', email: '' });
  const [status, setStatus] = useState('idle');
  const [bookingId, setBookingId] = useState(null);

  const onChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

  const submit = async (e) => {
    e.preventDefault();
    setStatus('submitting');
    try {
      const res = await fetch('/createBooking', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(form) });
      const json = await res.json();
      if (res.ok) {
        setBookingId(json.id);
        setStatus('success');
      } else {
        throw new Error(json.error || 'Failed');
      }
    } catch (e) {
      console.error(e);
      setStatus('error');
    }
  };

  return (
    <div style={{ maxWidth: 480, margin: '20px auto', fontFamily: 'sans-serif' }}>
      <h2>KARE Hall Booking</h2>
      <form onSubmit={submit}>
        <input name="hall" placeholder="Hall" value={form.hall} onChange={onChange} required />
        <input type="datetime-local" name="startDateTime" value={form.startDateTime} onChange={onChange} required />
        <input type="datetime-local" name="endDateTime" value={form.endDateTime} onChange={onChange} required />
        <input name="purpose" placeholder="Purpose" value={form.purpose} onChange={onChange} required />
        <input type="number" name="peopleCount" placeholder="Attendees" value={form.peopleCount} onChange={onChange} min={1} />
        <input name="bookedBy" placeholder="Your Name" value={form.bookedBy} onChange={onChange} />
        <input name="contact" placeholder="Contact" value={form.contact} onChange={onChange} />
        <input type="email" name="email" placeholder="Email" value={form.email} onChange={onChange} required />
        <button type="submit" disabled={status==='submitting'}>Create Booking</button>
      </form>
      {status==='success' && (
        <div style={{ marginTop: 12, padding: 12, background: '#e6f4ff', border: '1px solid #90caf9' }}>
          Booking request received — check your email. ID: {bookingId}
        </div>
      )}
      {status==='error' && <div style={{ color: 'red' }}>Failed to create booking. Try again.</div>}
    </div>
  );
}


