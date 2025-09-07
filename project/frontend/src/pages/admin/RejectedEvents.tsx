import React, { useState, useEffect } from 'react';
import { XCircle, User, Calendar, MapPin, Clock } from 'lucide-react';
import { FirestoreService } from '../../services/firestoreService';
import { Booking } from '../../types';
import { AdminLayout } from '../../components/AdminLayout';

export function RejectedEvents() {
  const [bookings, setBookings] = useState<Booking[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadRejectedBookings();
  }, []);

  const loadRejectedBookings = async () => {
    try {
      setLoading(true);
      const allBookings = await FirestoreService.getAllBookings();
      const rejectedBookings = allBookings.filter(booking => booking.status === 'rejected');
      setBookings(rejectedBookings);
    } catch (error) {
      console.error('Error loading rejected bookings:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <AdminLayout>
      <div className="space-y-6">
      <div className="bg-white rounded-lg shadow-sm p-6">
        <h1 className="text-2xl font-bold text-gray-800 mb-2">Rejected Events</h1>
        <p className="text-gray-600">View all rejected hall booking requests</p>
      </div>

      {bookings.length > 0 ? (
        <div className="space-y-4">
          {bookings.map((booking) => (
            <div key={booking.id} className="bg-white rounded-lg shadow-sm p-6 border-l-4 border-red-500">
              <div className="flex items-center space-x-3 mb-4">
                <div className="p-2 bg-red-100 rounded-lg">
                  <XCircle className="h-5 w-5 text-red-600" />
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
                    <Clock className="h-4 w-4 text-gray-400" />
                    <span className="text-sm text-gray-600">Time: {booking.timeFrom} - {booking.timeTo}</span>
                  </div>
                </div>
                <div className="space-y-2">
                  <div className="flex items-center space-x-2">
                    <MapPin className="h-4 w-4 text-gray-400" />
                    <span className="text-sm text-gray-600">Department: {booking.department}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className="text-sm text-gray-600">Purpose: {booking.purpose}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className="text-sm text-gray-600">Capacity: {booking.seatingCapacity} people</span>
                  </div>
                </div>
              </div>

              {booking.facilitiesRequired && booking.facilitiesRequired.length > 0 && (
                <div className="mb-4">
                  <p className="text-sm font-medium text-gray-700 mb-2">Required Facilities:</p>
                  <div className="flex flex-wrap gap-2">
                    {booking.facilitiesRequired.map((facility, index) => (
                      <span key={index} className="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full">
                        {facility}
                      </span>
                    ))}
                  </div>
                </div>
              )}

              <div className="flex justify-between items-center pt-4 border-t border-gray-200">
                <span className="text-sm text-gray-500">
                  Rejected on {formatDate(booking.updatedAt)}
                </span>
                <span className="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full font-medium">
                  Rejected
                </span>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow-sm p-12 text-center">
          <XCircle className="h-16 w-16 text-gray-400 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-800 mb-2">No rejected events</h3>
          <p className="text-gray-500">Rejected bookings will appear here</p>
        </div>
      )}
      </div>
    </AdminLayout>
  );
}
