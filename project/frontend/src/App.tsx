import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider, useAuth } from './contexts/AuthContext';
import { ProtectedRoute } from './components/ProtectedRoute';
import { NotificationProvider } from './components/NotificationToast';
import { DashboardLayout } from './components/DashboardLayout';

// Pages
import { Home } from './pages/Home';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { Dashboard } from './pages/Dashboard';
import { BookHall } from './pages/BookHall';
import { BookingForm } from './pages/BookingForm';
import { UpcomingEvents } from './pages/UpcomingEvents';
import { EventsHistory } from './pages/EventsHistory';
import { Notifications } from './pages/Notifications';
import { Profile } from './pages/Profile';
import { PendingRequests } from './pages/PendingRequests';
import { SetupAdmin } from './pages/SetupAdmin';

// Admin Pages
import { AdminDashboard } from './pages/admin/AdminDashboard';
import { AdminProfile } from './pages/admin/AdminProfile';
import { PendingRequests as AdminPendingRequests } from './pages/admin/PendingRequests';
import { ApprovedEvents } from './pages/admin/ApprovedEvents';
import { RejectedEvents } from './pages/admin/RejectedEvents';
import { AllBookings } from './pages/admin/AllBookings';
import { HallManagement } from './pages/admin/HallManagement';
import { Notifications as AdminNotifications } from './pages/admin/Notifications';
import { Reports } from './pages/admin/Reports';
import { CancelRequests } from './pages/admin/CancelRequests';

function AppContent() {
  const { currentUser } = useAuth();

  // Debug logging
  console.log('🔍 AppContent - currentUser:', currentUser);
  console.log('🔍 AppContent - user role:', currentUser?.role);
  console.log('🔍 AppContent - current path:', window.location.pathname);

  return (
    <Routes>
      <Route path="/" element={currentUser ? (currentUser.role === 'admin' ? <Navigate to="/admin/dashboard" replace /> : <Navigate to="/dashboard" replace />) : <Home />} />
      <Route path="/login" element={currentUser ? (currentUser.role === 'admin' ? <Navigate to="/admin/dashboard" replace /> : <Navigate to="/dashboard" replace />) : <Login />} />
      <Route path="/register" element={currentUser ? (currentUser.role === 'admin' ? <Navigate to="/admin/dashboard" replace /> : <Navigate to="/dashboard" replace />) : <Register />} />
      <Route path="/setup-admin" element={<SetupAdmin />} />
      
      {/* User Routes */}
      <Route path="/dashboard" element={
        <ProtectedRoute>
          {currentUser?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <Dashboard />
          )}
        </ProtectedRoute>
      } />
      <Route path="/dashboard/profile" element={
        <ProtectedRoute>
          {currentUser?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <Profile />
          )}
        </ProtectedRoute>
      } />
      <Route path="/dashboard/book-hall" element={
        <ProtectedRoute>
          {currentUser?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <BookHall />
          )}
        </ProtectedRoute>
      } />
      <Route path="/dashboard/book-hall/:hallId" element={
        <ProtectedRoute>
          {currentUser?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <BookingForm />
          )}
        </ProtectedRoute>
      } />
      <Route path="/dashboard/pending" element={
        <ProtectedRoute>
          {currentUser?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <PendingRequests />
          )}
        </ProtectedRoute>
      } />
      <Route path="/dashboard/upcoming" element={
        <ProtectedRoute>
          {currentUser?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <UpcomingEvents />
          )}
        </ProtectedRoute>
      } />
      <Route path="/dashboard/history" element={
        <ProtectedRoute>
          {currentUser?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <EventsHistory />
          )}
        </ProtectedRoute>
      } />
      <Route path="/dashboard/notifications" element={
        <ProtectedRoute>
          {currentUser?.role === 'admin' ? (
            <Navigate to="/admin/dashboard" replace />
          ) : (
            <Notifications />
          )}
        </ProtectedRoute>
      } />

      {/* Admin Routes */}
      <Route path="/admin" element={
        <ProtectedRoute adminOnly>
          <Navigate to="/admin/dashboard" replace />
        </ProtectedRoute>
      } />
      <Route path="/admin/dashboard" element={
        <ProtectedRoute adminOnly>
          <AdminDashboard />
        </ProtectedRoute>
      } />
      <Route path="/admin/profile" element={
        <ProtectedRoute adminOnly>
          <AdminProfile />
        </ProtectedRoute>
      } />
      {/* Keep legacy path but also support sidebar short path */}
      <Route path="/admin/pending-requests" element={
        <ProtectedRoute adminOnly>
          <AdminPendingRequests />
        </ProtectedRoute>
      } />
      <Route path="/admin/pending" element={
        <ProtectedRoute adminOnly>
          <AdminPendingRequests />
        </ProtectedRoute>
      } />
      <Route path="/admin/cancel-requests" element={
        <ProtectedRoute adminOnly>
          <CancelRequests />
        </ProtectedRoute>
      } />
      <Route path="/admin/approved-events" element={
        <ProtectedRoute adminOnly>
          <ApprovedEvents />
        </ProtectedRoute>
      } />
      <Route path="/admin/rejected-events" element={
        <ProtectedRoute adminOnly>
          <RejectedEvents />
        </ProtectedRoute>
      } />
      <Route path="/admin/all-bookings" element={
        <ProtectedRoute adminOnly>
          <AllBookings />
        </ProtectedRoute>
      } />
      <Route path="/admin/hall-management" element={
        <ProtectedRoute adminOnly>
          <HallManagement />
        </ProtectedRoute>
      } />
      <Route path="/admin/notifications" element={
        <ProtectedRoute adminOnly>
          <AdminNotifications />
        </ProtectedRoute>
      } />
      <Route path="/admin/reports" element={
        <ProtectedRoute adminOnly>
          <Reports />
        </ProtectedRoute>
      } />

      {/* Catch all */}
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

function App() {
  return (
    <AuthProvider>
      <NotificationProvider>
        <Router>
          <AppContent />
        </Router>
      </NotificationProvider>
    </AuthProvider>
  );
}

export default App;