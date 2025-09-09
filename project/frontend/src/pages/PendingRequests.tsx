import React, { useState, useEffect } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { useAuth } from '../contexts/AuthContext';
import { FirestoreService } from '../services/firestoreService';
import { Clock, AlertCircle, CheckCircle, XCircle } from 'lucide-react';
import { Booking } from '../types';

export function PendingRequests() {
  const { currentUser } = useAuth();
  const [pendingBookings, setPendingBookings] = useState<Booking[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadPendingBookings();
  }, [currentUser]);

  const loadPendingBookings = async () => {
    if (!currentUser) return;
    
    try {
      setLoading(true);
      const bookings = await FirestoreService.getUserBookings(currentUser.uid);
      const pending = bookings.filter(booking => booking.status === 'pending');
      setPendingBookings(pending);
    } catch (error) {
      console.error('Error loading pending bookings:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateValue: string | any) => {
    let date: Date;
    if (typeof dateValue === 'string') {
      date = new Date(dateValue);
    } else if (dateValue && dateValue.toDate) {
      date = dateValue.toDate();
    } else if (dateValue && dateValue.seconds) {
      date = new Date(dateValue.seconds * 1000);
    } else {
      date = new Date();
    }
    
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  if (loading) {
    return (
      <DashboardLayout>
        <div className="flex items-center justify-center py-12">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        </div>
      </DashboardLayout>
    );
  }

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">Pending Requests</h1>
          <p className="text-gray-600 mt-2">
            Your hall booking requests awaiting approval
          </p>
        </div>

        {pendingBookings.length === 0 ? (
          <div className="text-center py-12">
            <Clock className="h-16 w-16 text-gray-400 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-800 mb-2">No Pending Requests</h3>
            <p className="text-gray-500 mb-6">You don't have any booking requests waiting for approval.</p>
            <a
              href="/dashboard/book-hall"
              className="inline-flex items-center px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
            >
              Book a New Hall
            </a>
          </div>
        ) : (
          <div className="space-y-4">
            {pendingBookings.map((booking) => (
              <div key={booking.id || booking.bookingId} className="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-400">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-3 mb-3">
                      <h3 className="text-xl font-semibold text-gray-800">{booking.hallName}</h3>
                      <span className="inline-flex items-center space-x-1 px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <Clock className="h-4 w-4" />
                        <span>Pending Approval</span>
                      </span>
                    </div>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                      <div className="space-y-2">
                        <p className="text-sm text-gray-600">
                          <span className="font-medium">Purpose:</span> {booking.purpose}
                        </p>
                        <p className="text-sm text-gray-600">
                          <span className="font-medium">Date:</span> {booking.dates.join(', ')}
                        </p>
                        <p className="text-sm text-gray-600">
                          <span className="font-medium">Time:</span> {booking.timeFrom} - {booking.timeTo}
                        </p>
                      </div>
                      <div className="space-y-2">
                        <p className="text-sm text-gray-600">
                          <span className="font-medium">Capacity:</span> {booking.seatingCapacity} people
                        </p>
                        <p className="text-sm text-gray-600">
                          <span className="font-medium">Department:</span> {booking.department}
                        </p>
                        <p className="text-sm text-gray-600">
                          <span className="font-medium">Submitted:</span> {formatDate(booking.createdAt)}
                        </p>
                      </div>
                    </div>

                    {(booking.facilities || booking.facilitiesRequired) && (booking.facilities?.length > 0 || booking.facilitiesRequired?.length > 0) && (
                      <div className="mb-4">
                        <p className="text-sm font-medium text-gray-700 mb-2">Required Facilities:</p>
                        <div className="flex flex-wrap gap-2">
                          {(booking.facilities || booking.facilitiesRequired || []).map((facility, index) => (
                            <span key={index} className="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                              {facility}
                            </span>
                          ))}
                        </div>
                      </div>
                    )}

                    <div className="flex items-center space-x-2 text-yellow-600 bg-yellow-50 p-3 rounded-lg">
                      <AlertCircle className="h-5 w-5" />
                      <span className="text-sm font-medium">
                        Your booking request is under review by the admin. You will be notified once it's approved or rejected.
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </DashboardLayout>
  );
}






