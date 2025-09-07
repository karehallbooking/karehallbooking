export async function createBooking(data) {
  const res = await fetch('/createBooking', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)
  });
  if (!res.ok) throw new Error('Failed to create booking');
  return res.json();
}


