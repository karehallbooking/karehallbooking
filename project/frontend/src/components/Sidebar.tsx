import React, { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
  Home,
  Calendar,
  Clock,
  History,
  Bell,
  User,
  Users,
  CheckCircle,
  XCircle,
  Building,
  BarChart,
  Settings
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { FirestoreService } from '../services/firestoreService';

export function Sidebar() {
  const { currentUser } = useAuth();
  const location = useLocation();
  const [pendingCount, setPendingCount] = useState(0);
  const [notificationCount, setNotificationCount] = useState(0);

  const isActive = (path: string) => location.pathname === path;

  // Load pending bookings count and notification count
  useEffect(() => {
    const loadCounts = async () => {
      if (!currentUser) return;
      
      try {
        // Load pending bookings count
        const bookings = await FirestoreService.getUserBookings(currentUser.uid);
        const pending = bookings.filter(booking => booking.status === 'pending').length;
        setPendingCount(pending);

        // Load unread notifications count
        const notifications = await FirestoreService.getUserNotifications(currentUser.uid);
        const unread = notifications.filter(notification => !notification.read).length;
        setNotificationCount(unread);
      } catch (error) {
        console.error('Error loading counts:', error);
      }
    };

    loadCounts();
    
    // Refresh counts every 30 seconds
    const interval = setInterval(loadCounts, 30000);
    return () => clearInterval(interval);
  }, [currentUser]);

  const userMenuItems = [
    { icon: Home, label: 'Dashboard', path: '/dashboard' },
    { icon: User, label: 'Profile', path: '/dashboard/profile' },
    { icon: Calendar, label: 'Book Hall', path: '/dashboard/book-hall' },
    { icon: Clock, label: 'Pending Requests', path: '/dashboard/pending', count: pendingCount },
    { icon: CheckCircle, label: 'Upcoming Events', path: '/dashboard/upcoming' },
    { icon: History, label: 'Events History', path: '/dashboard/history' },
    { icon: Bell, label: 'Notifications', path: '/dashboard/notifications', count: notificationCount },
  ];

  const adminMenuItems = [
    { icon: Home, label: 'Dashboard', path: '/admin/dashboard' },
    { icon: User, label: 'Profile', path: '/admin/profile' },
    { icon: Clock, label: 'Pending Requests', path: '/admin/pending' },
    { icon: XCircle, label: 'Cancel Requests', path: '/admin/cancel-requests' },
    { icon: CheckCircle, label: 'Approved Events', path: '/admin/approved' },
    { icon: XCircle, label: 'Rejected Events', path: '/admin/rejected' },
    { icon: Users, label: 'All Bookings', path: '/admin/bookings' },
    { icon: Building, label: 'Hall Management', path: '/admin/halls' },
    { icon: Bell, label: 'Notifications', path: '/admin/notifications' },
    { icon: BarChart, label: 'Reports', path: '/admin/reports' },
  ];

  const menuItems = currentUser?.role === 'admin' ? adminMenuItems : userMenuItems;

  return (
    <div className="h-full flex flex-col">
      <nav className="flex-1 px-4 py-4 overflow-y-auto">
        <ul className="space-y-2">
          {menuItems.map((item) => {
            const Icon = item.icon;
            return (
              <li key={item.path}>
                <Link
                  to={item.path}
                  className={`block w-full px-3 py-2.5 rounded-lg transition-all duration-200 ${
                    isActive(item.path)
                      ? 'bg-primary text-white shadow-md'
                      : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                  }`}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                      <Icon className={`h-4 w-4 ${
                        isActive(item.path) ? 'text-white' : 'text-gray-600'
                      }`} />
                      <span className="font-medium text-sm">{item.label}</span>
                    </div>
                    {item.count !== undefined && item.count > 0 && (
                      <span className={`px-2 py-1 text-xs rounded-full font-semibold ${
                        isActive(item.path)
                          ? 'bg-white text-primary'
                          : 'bg-primary text-white'
                      }`}>
                        {item.count}
                      </span>
                    )}
                  </div>
                </Link>
              </li>
            );
          })}
        </ul>
      </nav>
    </div>
  );
}