import React, { useState, useEffect } from 'react';
import { DashboardLayout } from '../components/DashboardLayout';
import { Bell, CheckCircle, AlertCircle, Info, XCircle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { FirestoreService } from '../services/firestoreService';
import { Notification } from '../types';

export function Notifications() {
  const { currentUser } = useAuth();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);
  
  useEffect(() => {
    if (!currentUser) {
      setNotifications([]);
      setLoading(false);
      return;
    }

    setLoading(true);
    const unsubscribe = FirestoreService.subscribeToUserNotifications(currentUser.uid, (items) => {
      setNotifications(items);
      setLoading(false);
    });

    return () => {
      if (unsubscribe) unsubscribe();
    };
  }, [currentUser]);

  const getIcon = (type: string) => {
    switch (type) {
      case 'success':
        return <CheckCircle className="h-5 w-5 text-green-500" />;
      case 'warning':
        return <AlertCircle className="h-5 w-5 text-yellow-500" />;
      case 'error':
        return <XCircle className="h-5 w-5 text-red-500" />;
      default:
        return <Info className="h-5 w-5 text-blue-500" />;
    }
  };

  const formatDate = (dateValue: any) => {
    const date = dateValue?.toDate ? dateValue.toDate() : new Date(dateValue);
    return date.toLocaleDateString() + ' at ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

  const markAsRead = async (notificationId: string) => {
    try {
      await FirestoreService.markNotificationAsRead(notificationId);
      setNotifications(prev => 
        prev.map(notif => 
          notif.id === notificationId ? { ...notif, read: true } : notif
        )
      );
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-3xl font-bold text-gray-800">Notifications</h1>
            <p className="text-gray-600 mt-2">Stay updated with your booking status and announcements</p>
          </div>
          {notifications.length > 0 && notifications.some(n => !n.read) && (
            <button
              onClick={() => {
                notifications.forEach(notification => {
                  if (!notification.read) {
                    markAsRead(notification.id);
                  }
                });
              }}
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors"
            >
              Mark All as Read
            </button>
          )}
        </div>

        {loading ? (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p className="text-gray-600">Loading notifications...</p>
          </div>
        ) : notifications.length === 0 ? (
          <div className="bg-white rounded-lg shadow-md p-12 text-center">
            <Bell className="h-16 w-16 text-gray-300 mx-auto mb-4" />
            <h3 className="text-xl font-semibold text-gray-800 mb-2">No Notifications</h3>
            <p className="text-gray-600">You're all caught up! No new notifications to show.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {notifications.map((notification) => (
              <div
                key={notification.id}
                className={`bg-white rounded-lg shadow-md p-6 border-l-4 ${
                  notification.read ? 'border-gray-200' : 'border-primary'
                } ${
                  (notification.title.includes('REJECTED') || notification.title.includes('Rejected')) ? 'border-l-red-500 bg-red-50' : 
                  (notification.title.includes('APPROVED') || notification.title.includes('Approved')) ? 'border-l-green-500 bg-green-50' : ''
                }`}
              >
                <div className="flex items-start space-x-4">
                  <div className="flex-shrink-0">
                    {getIcon(notification.type)}
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                      <h3 className={`text-xl font-bold ${
                        notification.read ? 'text-gray-600' : 'text-gray-800'
                      } ${
                        notification.title.includes('REJECTED') || notification.title.includes('Rejected') ? 'text-red-600' : 
                        notification.title.includes('APPROVED') || notification.title.includes('Approved') ? 'text-green-600' : ''
                      }`}>
                        {notification.title.includes('Rejected') && !notification.title.includes('❌') 
                          ? notification.title.replace('Rejected', 'REJECTED ❌')
                          : notification.title.includes('Approved') && !notification.title.includes('🎉') && !notification.title.includes('✅')
                          ? notification.title.replace('Approved', 'APPROVED! 🎉')
                          : notification.title
                        }
                      </h3>
                      <div className="flex items-center space-x-2">
                        {!notification.read && (
                          <span className="w-2 h-2 bg-primary rounded-full"></span>
                        )}
                        {!notification.read && (
                          <button
                            onClick={() => markAsRead(notification.id)}
                            className="text-sm text-primary hover:text-primary-dark font-medium"
                          >
                            Mark as read
                          </button>
                        )}
                      </div>
                    </div>
                    <div className={`${
                      notification.read ? 'text-gray-500' : 'text-gray-700'
                    } mb-2`}>
                      {notification.message.split('\n').map((line, index) => {
                        // Skip lines that are part of the rejection reason section
                        if (line.includes('REJECTION REASON:') || (index > 0 && notification.message.split('\n')[index - 1].includes('REJECTION REASON:'))) {
                          return null;
                        }
                        
                        if (line.includes('REJECTED') && !line.includes('REJECTION REASON:')) {
                          return (
                            <div key={index}>
                              <p className="font-semibold text-lg">{line}</p>
                              {/* Show rejection reason in highlighted box for both old and new formats */}
                              {(notification.message.includes('REJECTION REASON:') || notification.message.includes('Reason:')) && (
                                <div className="mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                  <p className="text-red-800 font-semibold text-sm mb-1">
                                    📝 REJECTION REASON:
                                  </p>
                                  <p className="text-red-700 text-sm font-medium">
                                    {notification.message.includes('REJECTION REASON:') 
                                      ? notification.message.split('REJECTION REASON:')[1]?.trim()
                                      : notification.message.split('Reason:')[1]?.trim()
                                    }
                                  </p>
                                </div>
                              )}
                            </div>
                          );
                        }
                        
                        // Handle old format where reason is inline
                        if (line.includes('Reason:') && !line.includes('REJECTION REASON:')) {
                          const [mainText, reason] = line.split('Reason:');
                          return (
                            <div key={index}>
                              <p className="font-semibold text-lg">{mainText.trim()}</p>
                              <div className="mt-2 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <p className="text-red-800 font-semibold text-sm mb-1">
                                  📝 REJECTION REASON:
                                </p>
                                <p className="text-red-700 text-sm font-medium">
                                  {reason?.trim()}
                                </p>
                              </div>
                            </div>
                          );
                        }
                        
                        return (
                          <p key={index} className={line.includes('APPROVED') ? 'font-semibold text-lg' : ''}>
                            {line}
                          </p>
                        );
                      })}
                    </div>
                    <p className="text-sm text-gray-500">
                      {formatDate(notification.createdAt)}
                    </p>
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