import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { DashboardLayout } from '../components/DashboardLayout';
import { History, Calendar, Clock, MapPin, CheckCircle, XCircle } from 'lucide-react';
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

        <div className="bg-white rounded-lg shadow-md overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Event
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Hall
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date & Time
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {eventsHistory.map((event) => (
                  <tr key={event.id}>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div>
                        <div className="text-sm font-medium text-gray-900">{event.purpose}</div>
                        <div className="text-sm text-gray-500">{event.organizingDepartment}</div>
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {event.hallName}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      <div>{event.dates.join(', ')}</div>
                      <div className="text-gray-500">{event.timeFrom} - {event.timeTo}</div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
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
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}