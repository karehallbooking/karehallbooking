import React, { useEffect, useState } from 'react';
import { Clock, User, Calendar, MapPin, CheckCircle, XCircle } from 'lucide-react';
import { AdminLayout } from '../../components/AdminLayout';
import { FirestoreService } from '../../services/firestoreService';
import { Booking } from '../../types';

export function CancelRequests() {
  const [bookings, setBookings] = useState<Booking[]>([]);
  const [loading, setLoading] = useState(true);
  const [processing, setProcessing] = useState<string | null>(null);

  useEffect(() => {
    loadCancelRequests();
  }, []);

  const loadCancelRequests = async () => {
    try {
      setLoading(true);
      const allBookings = await FirestoreService.getAllBookings();
      const cancelRequests = allBookings.filter((b: any) => b.reviewStatus === 'pending' && b.reviewType === 'cancel');
      setBookings(cancelRequests);
    } catch (err) {
      console.error('Error loading cancel requests', err);
    } finally {
      setLoading(false);
    }
  };

  const approveCancellation = async (bookingId: string) => {
    try {
      setProcessing(bookingId);
      await FirestoreService.updateBooking(bookingId, {
        status: 'cancelled',
        reviewStatus: 'resolved' as any,
        reviewType: null as any,
        reviewReason: null as any,
      } as any);
      await loadCancelRequests();
    } catch (e) {
      console.error('approveCancellation error', e);
    } finally {
      setProcessing(null);
    }
  };

  const rejectCancellation = async (bookingId: string) => {
    try {
      setProcessing(bookingId);
      await FirestoreService.updateBooking(bookingId, {
        reviewStatus: 'rejected' as any,
        reviewType: null as any,
        reviewReason: null as any,
      } as any);
      await loadCancelRequests();
    } catch (e) {
      console.error('rejectCancellation error', e);
    } finally {
      setProcessing(null);
    }
  };

  const formatDate = (dateString: string) => new Date(dateString).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

  if (loading) {
    return (
      <AdminLayout>
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
      </AdminLayout>
    );
  }

  return (
    <AdminLayout>
      <div className="space-y-6">
        <div className="bg-white rounded-lg shadow-sm p-6">
          <h1 className="text-2xl font-bold text-gray-800 mb-2">Cancel Requests</h1>
          <p className="text-gray-600">Review and approve user-initiated cancellation requests</p>
        </div>

        {bookings.length > 0 ? (
          <div className="space-y-4">
            {bookings.map((booking) => (
              <div key={booking.id} className="bg-white rounded-lg shadow-sm p-6">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-3 mb-4">
                      <div className="p-2 bg-yellow-100 rounded-lg">
                        <Clock className="h-5 w-5 text-yellow-600" />
                      </div>
                      <div>
                        <h3 className="text-lg font-semibold text-gray-800">{booking.hallName}</h3>
                        <p className="text-sm text-gray-600">Requested by {booking.userName}</p>
                      </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                      <div className="space-y-2">
                        <div className="flex items-center space-x-2">
                          <User className="h-4 w-4 text-gray-400" />
                          <span className="text-sm text-gray-600">User: {booking.userName}</span>
                        </div>
                        <div className="flex items-center space-x-2">
                          <Calendar className="h-4 w-4 text-gray-400" />
                          <span className="text-sm text-gray-600">Date: {booking.dates.join(', ')}</span>
                        </div>
                        <div className="flex items-center space-x-2">
                          <span className="text-sm text-gray-600">Purpose: {booking.purpose}</span>
                        </div>
                      </div>
                      <div className="space-y-2">
                        <div className="flex items-center space-x-2">
                          <MapPin className="h-4 w-4 text-gray-400" />
                          <span className="text-sm text-gray-600">Department: {booking.department}</span>
                        </div>
                        {(booking as any).reviewReason && (
                          <div className="p-3 bg-red-50 border border-red-100 rounded text-sm text-red-700">
                            Cancel reason: {(booking as any).reviewReason}
                          </div>
                        )}
                      </div>
                    </div>

                    <div className="text-sm text-gray-500">Submitted on {formatDate((booking as any).updatedAt || booking.createdAt)}</div>
                  </div>

                  <div className="flex space-x-2 ml-4">
                    <button
                      onClick={() => approveCancellation(booking.id!)}
                      disabled={processing === booking.id}
                      className="flex items-center space-x-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50"
                    >
                      <CheckCircle className="h-4 w-4" />
                      <span>{processing === booking.id ? 'Processing...' : 'Approve'}</span>
                    </button>
                    <button
                      onClick={() => rejectCancellation(booking.id!)}
                      disabled={processing === booking.id}
                      className="flex items-center space-x-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                    >
                      <XCircle className="h-4 w-4" />
                      <span>{processing === booking.id ? 'Processing...' : 'Reject'}</span>
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="bg-white rounded-lg shadow-sm p-12 text-center">
            <Clock className="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <h3 className="text-lg font-medium text-gray-800 mb-2">No cancellation requests</h3>
            <p className="text-gray-500">There are currently no pending cancellation requests</p>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}



