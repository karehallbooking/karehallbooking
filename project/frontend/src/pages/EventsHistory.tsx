import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { DashboardLayout } from '../components/DashboardLayout';
import { History, Calendar, Clock, MapPin, CheckCircle, XCircle, AlertTriangle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { FirestoreService } from '../services/firestoreService';
import { Booking } from '../types';

export function EventsHistory() {
  const { currentUser } = useAuth();
  const [eventsHistory, setEventsHistory] = useState<Booking[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchEventsHistory = async () => {
      if (!currentUser) return;
      
      try {
        setLoading(true);
        const userBookings = await FirestoreService.getUserBookings(currentUser.uid);
        // Sort by creation date (newest first)
        const sortedBookings = userBookings.sort((a, b) => 
          new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
        );
        setEventsHistory(sortedBookings);
      } catch (error) {
        console.error('Error fetching events history:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchEventsHistory();
  }, [currentUser]);

  if (loading) {
    return (
      <DashboardLayout>
        <div className="space-y-6">
          <div>
            <h1 className="text-3xl font-bold text-gray-800">Events History</h1>
            <p className="text-gray-600 mt-2">Your past hall bookings and events</p>
          </div>
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p className="text-gray-600">Loading events history...</p>
          </div>
        </div>
      </DashboardLayout>
    );
  }

  if (eventsHistory.length === 0) {
    return (
      <DashboardLayout>
        <div className="space-y-6">
          <div>
            <h1 className="text-3xl font-bold text-gray-800">Events History</h1>
            <p className="text-gray-600 mt-2">Your past hall bookings and events</p>
          </div>

          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <History className="h-16 w-16 text-gray-300 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-800 mb-2">No Events History</h3>
            <p className="text-gray-600 mb-6">
              You have no bookings yet. Check the available halls and book your first event.
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
          <h1 className="text-3xl font-bold text-gray-800">Events History</h1>
          <p className="text-gray-600 mt-2">Your past hall bookings and events</p>
        </div>

        <div className="space-y-4">
          {eventsHistory.map((event) => (
            <div key={event.id} className="bg-white rounded-lg shadow-md p-6">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <div className="flex items-center space-x-3 mb-3">
                    <h3 className="text-lg font-semibold text-gray-900">{event.purpose}</h3>
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                      event.status === 'approved'
                        ? 'bg-green-100 text-green-800'
                        : event.status === 'rejected'
                        ? 'bg-red-100 text-red-800'
                        : event.status === 'cancelled'
                        ? 'bg-gray-200 text-gray-800'
                        : 'bg-yellow-100 text-yellow-800'
                    }`}>
                      {event.status === 'approved' && <CheckCircle className="h-3 w-3 mr-1" />}
                      {(event.status === 'rejected' || event.status === 'cancelled') && <XCircle className="h-3 w-3 mr-1" />}
                      {event.status === 'pending' && <Clock className="h-3 w-3 mr-1" />}
                      {event.status.charAt(0).toUpperCase() + event.status.slice(1)}
                    </span>
                  </div>
                  
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div className="space-y-2">
                      <div className="flex items-center space-x-2">
                        <MapPin className="h-4 w-4 text-gray-400" />
                        <span className="text-sm text-gray-600">Hall: {event.hallName}</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <Calendar className="h-4 w-4 text-gray-400" />
                        <span className="text-sm text-gray-600">Date: {event.dates.join(', ')}</span>
                      </div>
                      <div className="flex items-center space-x-2">
                        <Clock className="h-4 w-4 text-gray-400" />
                        <span className="text-sm text-gray-600">Time: {event.timeFrom} - {event.timeTo}</span>
                      </div>
                    </div>
                    <div className="space-y-2">
                      <div className="text-sm text-gray-600">
                        <span className="font-medium">Department:</span> {event.organizingDepartment}
                      </div>
                      <div className="text-sm text-gray-600">
                        <span className="font-medium">Capacity:</span> {event.seatingCapacity} people
                      </div>
                      <div className="text-sm text-gray-600">
                        <span className="font-medium">Submitted:</span> {new Date(event.createdAt).toLocaleDateString()}
                      </div>
                    </div>
                  </div>

                  {/* Show rejection reason if booking is rejected */}
                  {event.status === 'rejected' && event.rejectionReason && (
                    <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                      <div className="flex items-start space-x-2">
                        <AlertTriangle className="h-5 w-5 text-red-600 mt-0.5 flex-shrink-0" />
                        <div>
                          <h4 className="text-sm font-medium text-red-800 mb-1">Rejection Reason</h4>
                          <p className="text-sm text-red-700">{event.rejectionReason}</p>
                        </div>
                      </div>
                    </div>
                  )}

                  {/* Show cancellation rejection reason if cancellation was rejected */}
                  {(event as any).cancellationRejectionReason && (
                    <div className="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                      <div className="flex items-start space-x-2">
                        <AlertTriangle className="h-5 w-5 text-orange-600 mt-0.5 flex-shrink-0" />
                        <div>
                          <h4 className="text-sm font-medium text-orange-800 mb-1">Cancellation Rejection Reason</h4>
                          <p className="text-sm text-orange-700">{(event as any).cancellationRejectionReason}</p>
                        </div>
                      </div>
                    </div>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </DashboardLayout>
  );
}