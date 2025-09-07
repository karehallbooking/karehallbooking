import React, { useState, useEffect } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { useAuth } from '../contexts/AuthContext';
import { Calendar, Clock, Building, Bell, CheckCircle, XCircle, AlertCircle } from 'lucide-react';
import { FirestoreService } from '../services/firestoreService';
import { Booking } from '../types';

export function Dashboard() {
  const { currentUser } = useAuth();
  const [userBookings, setUserBookings] = useState<Booking[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadUserBookings();
  }, [currentUser]);

  const loadUserBookings = async () => {
    if (!currentUser) return;
    
    try {
      setLoading(true);
      const bookings = await FirestoreService.getUserBookings(currentUser.uid);
      setUserBookings(bookings);
    } catch (error) {
      console.error('Error loading user bookings:', error);
    } finally {
      setLoading(false);
    }
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'pending': return <AlertCircle className="h-4 w-4 text-yellow-600" />;
      case 'approved': return <CheckCircle className="h-4 w-4 text-green-600" />;
      case 'rejected': return <XCircle className="h-4 w-4 text-red-600" />;
      default: return <AlertCircle className="h-4 w-4 text-gray-600" />;
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'pending': return 'bg-yellow-100 text-yellow-800';
      case 'approved': return 'bg-green-100 text-green-800';
      case 'rejected': return 'bg-red-100 text-red-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  const formatDate = (dateValue: string | any) => {
    let date: Date;
    if (typeof dateValue === 'string') {
      date = new Date(dateValue);
    } else if (dateValue && dateValue.toDate) {
      // Firebase Timestamp
      date = dateValue.toDate();
    } else if (dateValue && dateValue.seconds) {
      // Firebase Timestamp with seconds
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

  const totalBookings = userBookings.length;
  const pendingBookings = userBookings.filter(b => b.status === 'pending').length;
  const approvedBookings = userBookings.filter(b => b.status === 'approved').length;
  const rejectedBookings = userBookings.filter(b => b.status === 'rejected').length;

  const stats = [
    {
      title: 'Total Bookings',
      value: totalBookings.toString(),
      icon: Calendar,
      color: 'bg-blue-500'
    },
    {
      title: 'Pending Approval',
      value: pendingBookings.toString(),
      icon: Clock,
      color: 'bg-yellow-500'
    },
    {
      title: 'Approved Events',
      value: approvedBookings.toString(),
      icon: CheckCircle,
      color: 'bg-green-500'
    },
    {
      title: 'Rejected Events',
      value: rejectedBookings.toString(),
      icon: XCircle,
      color: 'bg-red-500'
    }
  ];

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-800">
            Welcome, {currentUser?.name}
          </h1>
          <p className="text-gray-600 mt-2">
            Manage your hall bookings and events from your dashboard
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {stats.map((stat) => {
            const Icon = stat.icon;
            return (
              <div key={stat.title} className="bg-white rounded-lg shadow-md p-6">
                <div className="flex items-center">
                  <div className={`${stat.color} p-3 rounded-lg`}>
                    <Icon className="h-6 w-6 text-white" />
                  </div>
                  <div className="ml-4">
                    <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                    <p className="text-2xl font-bold text-gray-800">{stat.value}</p>
                  </div>
                </div>
              </div>
            );
          })}
        </div>


        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
            <div className="space-y-3">
              <a
                href="/dashboard/book-hall"
                className="block w-full text-left px-4 py-3 bg-primary text-white rounded-lg hover:bg-primary-dark transition-all duration-200 shadow-md font-medium"
              >
                Book a New Hall
              </a>
              <a
                href="/dashboard/pending"
                className="block w-full text-left px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium"
              >
                View Pending Requests
              </a>
              <a
                href="/dashboard/upcoming"
                className="block w-full text-left px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium"
              >
                View Upcoming Events
              </a>
              <a
                href="/dashboard/notifications"
                className="block w-full text-left px-4 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-all duration-200 font-medium"
              >
                Check Notifications
              </a>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow-md p-6">
            <h3 className="text-lg font-semibold text-gray-800 mb-4">Booking Summary</h3>
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Total Bookings</span>
                <span className="text-sm font-medium text-gray-800">{totalBookings}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Pending Approval</span>
                <span className="text-sm font-medium text-yellow-600">{pendingBookings}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Approved</span>
                <span className="text-sm font-medium text-green-600">{approvedBookings}</span>
              </div>
              <div className="flex justify-between items-center">
                <span className="text-sm text-gray-600">Rejected</span>
                <span className="text-sm font-medium text-red-600">{rejectedBookings}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </DashboardLayout>
  );
}