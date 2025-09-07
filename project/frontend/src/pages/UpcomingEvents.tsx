import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { DashboardLayout } from '../components/DashboardLayout';
import { NotificationModal } from '../components/NotificationModal';
import { Calendar, Clock, MapPin, Users, XCircle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { FirestoreService } from '../services/firestoreService';
import { Booking } from '../types';

export function UpcomingEvents() {
  const { currentUser } = useAuth();
  const [upcomingEvents, setUpcomingEvents] = useState<Booking[]>([]);
  const [loading, setLoading] = useState(true);
  const [cancelTarget, setCancelTarget] = useState<Booking | null>(null);
  const [cancelReason, setCancelReason] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [notifOpen, setNotifOpen] = useState(false);
  const [notifMessage, setNotifMessage] = useState('');

  useEffect(() => {
    const fetchUpcomingEvents = async () => {
      if (!currentUser) return;
      
      try {
        setLoading(true);
        const userBookings = await FirestoreService.getUserBookings(currentUser.uid);
        // Filter for approved bookings that are upcoming (today or later)
        const startOfToday = new Date();
        startOfToday.setHours(0, 0, 0, 0);
        const upcoming = userBookings.filter((booking) => {
          if (booking.status !== 'approved') return false;
          const primaryDateStr = (booking as any).checkInDate || (booking.dates && booking.dates[0]);
          if (!primaryDateStr) return false;
          const d = new Date(primaryDateStr);
          return d.getTime() >= startOfToday.getTime();
        });
        setUpcomingEvents(upcoming);
      } catch (error) {
        console.error('Error fetching upcoming events:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchUpcomingEvents();
  }, [currentUser]);

  const submitCancellation = async () => {
    if (!cancelTarget || !currentUser) return;
    if (!cancelReason.trim()) {
      alert('Please provide a reason for cancellation.');
      return;
    }
    try {
      setSubmitting(true);
      const idToken = (await (currentUser as any).getIdToken?.()) || '';
      if (idToken) {
        await FirestoreService.requestBookingCancellation(cancelTarget.id!, cancelReason.trim(), idToken);
      } else {
        // Fallback to direct Firestore update if token not available
        await FirestoreService.updateBooking(cancelTarget.id!, {
          reviewStatus: 'pending',
          reviewType: 'cancel',
          reviewReason: cancelReason.trim(),
          reviewRequestedBy: (currentUser as any).uid,
          updatedAt: new Date().toISOString()
        } as any);
      }
      setCancelTarget(null);
      setCancelReason('');
      // Refresh list
      const userBookings = await FirestoreService.getUserBookings(currentUser.uid);
      const startOfToday = new Date(); startOfToday.setHours(0,0,0,0);
      const upcoming = userBookings.filter((booking) => {
        if (booking.status !== 'approved') return false;
        const primaryDateStr = (booking as any).checkInDate || (booking.dates && booking.dates[0]);
        if (!primaryDateStr) return false;
        const d = new Date(primaryDateStr);
        return d.getTime() >= startOfToday.getTime();
      });
      setUpcomingEvents(upcoming);
      setNotifMessage('Please wait for approval. Your cancellation request was submitted.');
      setNotifOpen(true);
    } catch (e) {
      console.error('cancel submit error', e);
      setNotifMessage('Failed to submit cancellation. Please try again.');
      setNotifOpen(true);
    } finally {
      setSubmitting(false);
    }
  };

  // Open modal to collect cancellation reason
  const cancelBooking = (booking: Booking) => {
    setCancelTarget(booking);
  };

  if (loading) {
    return (
      <DashboardLayout>
        <div className="space-y-6">
          <div>
            <h1 className="text-3xl font-bold text-gray-800">Upcoming Events</h1>
            <p className="text-gray-600 mt-2">Your scheduled hall bookings and events</p>
          </div>
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p className="text-gray-600">Loading upcoming events...</p>
          </div>
        </div>
      </DashboardLayout>
    );
  }

  if (upcomingEvents.length === 0) {
    return (
      <DashboardLayout>
        <div className="space-y-6">
          <div>
            <h1 className="text-3xl font-bold text-gray-800">Upcoming Events</h1>
            <p className="text-gray-600 mt-2">Your scheduled hall bookings and events</p>
          </div>

          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <Calendar className="h-16 w-16 text-gray-300 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-800 mb-2">No Upcoming Events</h3>
            <p className="text-gray-600 mb-6">
              You have no approved bookings yet. Check your booking status or book a new hall.
            </p>
            <Link
              to="/dashboard/book-hall"
              className="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
            >
              <Calendar className="h-5 w-5 mr-2" />
              Book a Hall
            </Link>
          </div>
        </div>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Upcoming Events</h1>
          <p className="text-gray-600 mt-2">Your scheduled hall bookings and events</p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {upcomingEvents.map((booking) => {
            const isCancelPending = (booking as any).reviewStatus === 'pending' && (booking as any).reviewType === 'cancel';
            return (
            <div key={booking.id} className="bg-white rounded-lg shadow-md p-6">
              <div className="flex justify-between items-start mb-4">
                <div>
                  <h3 className="text-lg font-semibold text-gray-800">{booking.purpose}</h3>
                  <p className="text-gray-600">{booking.hallName}</p>
                </div>
                <div className="flex items-center space-x-2 pointer-events-auto">
                  {isCancelPending ? (
                    <span className="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Cancel requested</span>
                  ) : (
                    <span className="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Approved</span>
                  )}
                  <button
                    type="button"
                    onClick={() => cancelBooking(booking)}
                    onKeyDown={(e) => { if (e.key === 'Enter' || e.key === ' ') cancelBooking(booking); }}
                    disabled={isCancelPending}
                    className={`inline-flex items-center space-x-1 px-3 py-1 text-sm rounded focus:outline-none focus:ring-2 active:scale-[.98] select-none ${isCancelPending ? 'bg-gray-300 text-gray-600 cursor-not-allowed' : 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-400 cursor-pointer'}`}
                    role="button"
                    tabIndex={0}
                    aria-label="Cancel booking"
                  >
                    <XCircle className="h-4 w-4" />
                    <span>{isCancelPending ? 'Requested' : 'Cancel'}</span>
                  </button>
                </div>
              </div>

              <div className="space-y-2 text-sm text-gray-600">
                {isCancelPending && (
                  <div className="p-3 bg-yellow-50 border border-yellow-100 rounded text-yellow-800">
                    Cancellation request submitted. Please wait for admin approval.
                  </div>
                )}
                <div className="flex items-center space-x-2">
                  <MapPin className="h-4 w-4" />
                  <span>{booking.hallName}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <Calendar className="h-4 w-4" />
                  <span>{booking.dates.join(', ')}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <Clock className="h-4 w-4" />
                  <span>{booking.timeFrom} - {booking.timeTo}</span>
                </div>
                <div className="flex items-center space-x-2">
                  <Users className="h-4 w-4" />
                  <span>{booking.seatingCapacity} people</span>
                </div>
              </div>
            </div>
          );})}
        </div>
        {/* Cancel modal */}
        {cancelTarget && (
          <div className="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 w-full max-w-md">
              <h4 className="text-lg font-semibold text-gray-800 mb-2">Cancel Booking</h4>
              <p className="text-sm text-gray-600 mb-4">Please provide a reason for cancelling "{cancelTarget.purpose}".</p>
              <textarea
                className="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-primary mb-4"
                rows={4}
                placeholder="Reason for cancellation"
                value={cancelReason}
                onChange={(e) => setCancelReason(e.target.value)}
              />
              <div className="flex justify-end space-x-2">
                <button onClick={() => { setCancelTarget(null); setCancelReason(''); }} className="px-4 py-2 border rounded-lg">Close</button>
                <button onClick={submitCancellation} disabled={submitting} className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">{submitting ? 'Submitting...' : 'Submit Cancellation'}</button>
              </div>
            </div>
          </div>
        )}

        <NotificationModal
          open={notifOpen}
          title="Notification"
          message={notifMessage}
          confirmText="OK"
          onClose={() => setNotifOpen(false)}
        />
      </div>
    </DashboardLayout>
  );
}