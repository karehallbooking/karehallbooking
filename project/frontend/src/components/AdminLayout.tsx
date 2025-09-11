import React, { useState, useEffect } from 'react';
import { Link, useLocation } from 'react-router-dom';
import { 
  LayoutDashboard, 
  Users,
  Clock, 
  CheckCircle, 
  XCircle, 
  Calendar, 
  Building, 
  Bell, 
  BarChart3,
  Menu,
  X
} from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { AppNavbar } from './AppNavbar';
import { FirestoreService } from '../services/firestoreService';

interface AdminLayoutProps {
  children: React.ReactNode;
}

export function AdminLayout({ children }: AdminLayoutProps) {
  const { currentUser } = useAuth();
  const location = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  
  // State for counts
  const [pendingCount, setPendingCount] = useState(0);
  const [cancelCount, setCancelCount] = useState(0);

  // Function to refresh pending count
  const refreshPendingCount = async () => {
    if (!currentUser) return;
    
    try {
      const allBookings = await FirestoreService.getAllBookings();
      const pending = allBookings.filter((booking: any) => booking.status === 'pending' && booking.reviewType !== 'cancel').length;
      const cancels = allBookings.filter((booking: any) => booking.reviewStatus === 'pending' && booking.reviewType === 'cancel').length;
      setPendingCount(pending);
      setCancelCount(cancels);
    } catch (error) {
      console.error('Error refreshing pending count:', error);
    }
  };

  // Load pending count on component mount
  useEffect(() => {
    refreshPendingCount();
    
    // Set up real-time updates every 30 seconds
    const interval = setInterval(refreshPendingCount, 30000);
    
    return () => clearInterval(interval);
  }, [currentUser]);

  // Update pending count when location changes
  useEffect(() => {
    refreshPendingCount();
  }, [location.pathname, currentUser]);

  // Logout handled in AppNavbar

  const navigation = [
    { name: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Current Users', href: '/admin/profile', icon: Users },
    { name: 'Pending Requests', href: '/admin/pending-requests', icon: Clock, count: pendingCount },
    { name: 'Cancel Requests', href: '/admin/cancel-requests', icon: XCircle, count: cancelCount },
    { name: 'Approved Events', href: '/admin/approved-events', icon: CheckCircle },
    { name: 'Rejected Events', href: '/admin/rejected-events', icon: XCircle },
    { name: 'All Bookings', href: '/admin/all-bookings', icon: Calendar },
    { name: 'Hall Management', href: '/admin/hall-management', icon: Building },
    { name: 'Notifications', href: '/admin/notifications', icon: Bell },
    { name: 'Reports', href: '/admin/reports', icon: BarChart3 },
  ];

  const isActive = (href: string) => location.pathname === href;

  return (
    <div className="min-h-screen bg-gray-50">
      <AppNavbar showUserMenu={true} />
      {/* Mobile sidebar */}
      <div className={`fixed inset-0 z-50 lg:hidden ${sidebarOpen ? 'block' : 'hidden'}`}>
        <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)} />
        <div className="fixed top-24 bottom-0 left-0 flex w-80 flex-col bg-white">
          <div className="flex items-center justify-end px-4 py-3 border-b border-gray-200">
            <button
              onClick={() => setSidebarOpen(false)}
              className="text-gray-400 hover:text-gray-600"
            >
              <X className="h-6 w-6" />
            </button>
          </div>
          <nav className="px-4 py-4 overflow-y-auto flex-1">
            <ul className="space-y-2">
              {navigation.map((item) => {
                const Icon = item.icon;
                return (
                  <li key={item.name}>
                    <Link
                      to={item.href}
                      onClick={() => setSidebarOpen(false)}
                      className={`block w-full px-3 py-2.5 rounded-lg transition-all duration-200 ${
                        isActive(item.href)
                          ? 'bg-primary text-white shadow-md'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      }`}
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                          <Icon className={`h-4 w-4 ${
                            isActive(item.href) ? 'text-white' : 'text-gray-600'
                          }`} />
                          <span className="font-medium text-sm">{item.name}</span>
                        </div>
                        {item.count !== undefined && item.count > 0 && (
                          <span className={`px-2 py-1 text-xs font-bold rounded-full ${
                            isActive(item.href)
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
      </div>

      {/* Desktop sidebar */}
      <div className="hidden lg:fixed lg:top-24 lg:bottom-0 lg:flex lg:w-80 lg:flex-col">
        <div className="flex flex-col flex-grow bg-white border-r border-gray-200 h-full">
          <nav className="px-4 py-4 overflow-y-auto flex-1">
            <ul className="space-y-2">
              {navigation.map((item) => {
                const Icon = item.icon;
                return (
                  <li key={item.name}>
                    <Link
                      to={item.href}
                      className={`block w-full px-3 py-2.5 rounded-lg transition-all duration-200 ${
                        isActive(item.href)
                          ? 'bg-primary text-white shadow-md'
                          : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                      }`}
                    >
                      <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                          <Icon className={`h-4 w-4 ${
                            isActive(item.href) ? 'text-white' : 'text-gray-600'
                          }`} />
                          <span className="font-medium text-sm">{item.name}</span>
                        </div>
                        {item.count !== undefined && item.count > 0 && (
                          <span className={`px-2 py-1 text-xs font-bold rounded-full ${
                            isActive(item.href)
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
      </div>

      {/* Main content */}
      <div className="lg:pl-80 pt-24">
        {/* Mobile menu button */}
        <div className="lg:hidden fixed top-24 left-4 z-30">
          <button
            type="button"
            className="p-2 text-gray-700 bg-white rounded-md shadow-md"
            onClick={() => setSidebarOpen(true)}
          >
            <Menu className="h-6 w-6" />
          </button>
        </div>

        {/* Page content */}
        <main className="py-6">
          <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {children}
          </div>
        </main>
      </div>
    </div>
  );
}

