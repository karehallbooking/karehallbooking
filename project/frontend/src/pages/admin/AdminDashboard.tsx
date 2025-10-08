import React, { useState, useEffect } from 'react';
import { Calendar, Clock, Building, Bell, TrendingUp, Users, Key } from 'lucide-react';
import { FirestoreService } from '../../services/firestoreService';
import { Booking, Hall, Notification } from '../../types';
import { AdminLayout } from '../../components/AdminLayout';
import AdminPasswordModal from '../../components/AdminPasswordModal';
import { auth } from '../../config/firebase';

export function AdminDashboard() {
  const [stats, setStats] = useState({
    totalBookings: 0,
    pendingRequests: 0,
    approvedEvents: 0,
    availableHalls: 0,
    notifications: 0
  });
  const [recentBookings, setRecentBookings] = useState<Booking[]>([]);
  const [topHalls, setTopHalls] = useState<Array<{ hallName: string; count: number }>>([]);
  const [weekSchedule, setWeekSchedule] = useState<Record<string, Booking[]>>({});
  const [loading, setLoading] = useState(true);
  const [showPasswordModal, setShowPasswordModal] = useState(false);
  const [hasPasswordProvider, setHasPasswordProvider] = useState(false);

  useEffect(() => {
    loadDashboardData();
  }, []);

  // Check if admin has password provider
  useEffect(() => {
    if (auth.currentUser) {
      const providerData = auth.currentUser.providerData;
      const hasPassword = providerData.some(provider => provider.providerId === 'password');
      setHasPasswordProvider(hasPassword);
      console.log('🔍 Admin providers:', providerData.map(p => p.providerId));
      console.log('🔍 Admin has password provider:', hasPassword);
    }
  }, []);

  const loadDashboardData = async () => {
    try {
      setLoading(true);
      
      // Load all bookings to calculate stats
      const allBookings = await FirestoreService.getAllBookings();
      const halls = await FirestoreService.getHalls();
      
      // Calculate statistics
      const totalBookings = allBookings.length;
      const pendingRequests = allBookings.filter(b => b.status === 'pending').length;
      const approvedEvents = allBookings.filter(b => b.status === 'approved').length;
      const availableHalls = halls.length;
      
      // Get recent bookings (last 5)
      const recent = allBookings
        .sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime())
        .slice(0, 5);
      
      // Compute top booked halls (by count)
      const hallCountMap: Record<string, number> = {};
      for (const b of allBookings) {
        const name = b.hallName || 'Unknown Hall';
        hallCountMap[name] = (hallCountMap[name] || 0) + 1;
      }
      const ranked = Object.entries(hallCountMap)
        .map(([hallName, count]) => ({ hallName, count }))
        .sort((a, b) => b.count - a.count)
        .slice(0, 3);

      setTopHalls(ranked);

      // Build next 7-day schedule
      const schedule: Record<string, Booking[]> = {};
      const today = new Date();
      for (let i = 0; i < 7; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() + i);
        const key = d.toISOString().slice(0, 10); // yyyy-mm-dd
        schedule[key] = [];
      }

      for (const booking of allBookings) {
        // Prefer dates array; fallback to checkInDate
        const bookingDates: string[] = Array.isArray(booking.dates) && booking.dates.length
          ? (booking.dates as unknown as string[])
          : (booking.checkInDate ? [booking.checkInDate] : []);

        for (const key of Object.keys(schedule)) {
          if (bookingDates.includes(key)) {
            schedule[key].push(booking as Booking);
          }
        }
      }

      setWeekSchedule(schedule);

      setStats({
        totalBookings,
        pendingRequests,
        approvedEvents,
        availableHalls,
        notifications: 0 // We'll implement notifications later
      });
      
      setRecentBookings(recent);
    } catch (error) {
      console.error('Error loading dashboard data:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'text-yellow-600 bg-yellow-100';
      case 'approved': return 'text-green-600 bg-green-100';
      case 'rejected': return 'text-red-600 bg-red-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'pending': return <Clock className="h-4 w-4" />;
      case 'approved': return <Calendar className="h-4 w-4" />;
      case 'rejected': return <Users className="h-4 w-4" />;
      default: return <Clock className="h-4 w-4" />;
    }
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
      {/* Welcome Section */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">Welcome, KARE Hall Admin</h1>
            <p className="text-gray-600">Manage your hall bookings and events from your dashboard</p>
          </div>
          <button
            onClick={() => setShowPasswordModal(true)}
            className="flex items-center space-x-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            <Key className="h-4 w-4" />
            <span>{hasPasswordProvider ? 'Update Password' : 'Add Password'}</span>
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="p-2 bg-blue-100 rounded-lg">
              <Calendar className="h-6 w-6 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Bookings</p>
              <p className="text-2xl font-bold text-gray-900">{stats.totalBookings}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="p-2 bg-yellow-100 rounded-lg">
              <Clock className="h-6 w-6 text-yellow-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Pending Requests</p>
              <p className="text-2xl font-bold text-gray-900">{stats.pendingRequests}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="p-2 bg-green-100 rounded-lg">
              <TrendingUp className="h-6 w-6 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Approved Events</p>
              <p className="text-2xl font-bold text-gray-900">{stats.approvedEvents}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="p-2 bg-purple-100 rounded-lg">
              <Building className="h-6 w-6 text-purple-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Available Halls</p>
              <p className="text-2xl font-bold text-gray-900">{stats.availableHalls}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Insights will be shown after Recent Bookings & Quick Actions */}

      {/* Recent Activity and Quick Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Recent Bookings */}
        <div className="bg-white rounded-lg shadow-sm p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Recent Bookings</h3>
          {recentBookings.length > 0 ? (
            <div className="space-y-3">
              {recentBookings.map((booking) => (
                <div key={booking.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div className="flex items-center space-x-3">
                    <div className={`p-2 rounded-full ${getStatusColor(booking.status)}`}>
                      {getStatusIcon(booking.status)}
                    </div>
                    <div>
                      <p className="font-medium text-gray-800">{booking.hallName}</p>
                      <p className="text-sm text-gray-600">{booking.userName} - {booking.purpose}</p>
                    </div>
                  </div>
                  <span className={`px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(booking.status)}`}>
                    {booking.status}
                  </span>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <Calendar className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-500">No bookings yet</p>
            </div>
          )}
        </div>

        {/* Quick Actions */}
        <div className="bg-white rounded-lg shadow-sm p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
          <div className="space-y-3">
            <button className="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors">
              Review Pending Requests
            </button>
            <button className="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-200 transition-colors">
              Manage Halls
            </button>
            <button className="w-full bg-gray-100 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-200 transition-colors">
              View Reports
            </button>
          </div>
        </div>
      </div>

      {/* Insights: Top Halls and Upcoming Timeline (placed after the above section) */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Top Booked Halls */}
        <div className="bg-white rounded-lg shadow-sm p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Top Booked Halls</h3>
          {topHalls.length > 0 ? (
            <div className="space-y-4">
              {topHalls.map((item, idx) => {
                const max = topHalls[0].count || 1;
                const pct = Math.round((item.count / max) * 100);
                const medal = idx === 0 ? '🥇' : idx === 1 ? '🥈' : '🥉';
                return (
                  <div key={item.hallName} className="p-4 bg-gray-50 rounded-lg">
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center space-x-2">
                        <span className="text-xl">{medal}</span>
                        <span className="font-medium text-gray-800">{item.hallName}</span>
                      </div>
                      <span className="text-sm text-gray-600">{item.count} bookings</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                      <div className="h-2 bg-blue-600" style={{ width: `${pct}%` }}></div>
                    </div>
                    <div className="text-right text-xs text-gray-500 mt-1">{pct}%</div>
                  </div>
                );
              })}
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">No bookings data yet</div>
          )}
        </div>

        {/* Upcoming Events - Vertical Timeline */}
        <div className="bg-white rounded-lg shadow-sm p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Upcoming Events (Next 7 Days)</h3>
          <div className="space-y-3 max-h-96 overflow-y-auto pr-1">
            {Object.keys(weekSchedule).map((key) => {
              const dateObj = new Date(key);
              const label = dateObj.toLocaleDateString(undefined, { month: 'short', day: 'numeric', weekday: 'short' });
              const events = weekSchedule[key] || [];
              return (
                <div key={key} className="bg-gray-50 rounded-lg p-4 border border-gray-100">
                  <div className="flex items-center justify-between mb-2">
                    <div className="font-semibold text-gray-800">{label}</div>
                    <span className={`text-xs px-2 py-0.5 rounded-full ${events.length > 0 ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'}`}>{events.length} {events.length === 1 ? 'event' : 'events'}</span>
                  </div>
                  <div className="space-y-2">
                    {events.length === 0 ? (
                      <div className="text-xs text-gray-500">No events</div>
                    ) : (
                      events.slice(0, 5).map((ev) => (
                        <div key={ev.id} className="text-xs bg-white border border-gray-200 rounded-md p-2">
                          <div className="font-medium text-gray-800">{ev.hallName}</div>
                          <div className="text-gray-600">
                            {(ev.timeFrom || ev.checkInTime || '').toString()} - {(ev.timeTo || ev.checkOutTime || '').toString()}
                          </div>
                          <span className={`inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] ${ev.status === 'approved' ? 'bg-green-100 text-green-700' : ev.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'}`}>{ev.status}</span>
                        </div>
                      ))
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>
      </div>

      {/* Admin Password Modal */}
      <AdminPasswordModal
        isOpen={showPasswordModal}
        onClose={() => setShowPasswordModal(false)}
        onSuccess={() => {
          console.log('✅ Admin password updated successfully!');
          // Refresh the provider status
          if (auth.currentUser) {
            const providerData = auth.currentUser.providerData;
            const hasPassword = providerData.some(provider => provider.providerId === 'password');
            setHasPasswordProvider(hasPassword);
            console.log('🔄 Updated admin provider status:', hasPassword);
          }
        }}
      />
    </AdminLayout>
  );
}
