import React, { useState } from 'react';
import { 
  Users, 
  Mail, 
  Phone, 
  Building, 
  GraduationCap, 
  Home, 
  MapPin, 
  Search, 
  Filter,
  Shield,
  ShieldCheck,
  AlertTriangle,
  CheckCircle,
  XCircle
} from 'lucide-react';
import { FirestoreService } from '../services/firestoreService';
import { User as UserType } from '../types';
import { useAuth } from '../contexts/AuthContext';
import { useNotification } from './NotificationToast';

interface UsersGridProps {
  users: UserType[];
  loading: boolean;
  onRefresh: () => void;
}

interface ConfirmationModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  message: string;
  action: 'promote' | 'demote';
  userName: string;
  loading?: boolean;
}

function ConfirmationModal({ 
  isOpen, 
  onClose, 
  onConfirm, 
  title, 
  message, 
  action, 
  userName, 
  loading = false 
}: ConfirmationModalProps) {
  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto">
      <div className="flex min-h-screen items-center justify-center p-4">
        <div className="fixed inset-0 bg-gray-600 bg-opacity-75 transition-opacity" onClick={onClose} />
        <div className="relative bg-white rounded-lg shadow-xl max-w-md w-full">
          <div className="flex items-center justify-between p-6 border-b border-gray-200">
            <div className="flex items-center space-x-3">
              <div className={`p-2 rounded-full ${
                action === 'promote' ? 'bg-blue-100' : 'bg-red-100'
              }`}>
                {action === 'promote' ? (
                  <ShieldCheck className="h-6 w-6 text-blue-600" />
                ) : (
                  <Shield className="h-6 w-6 text-red-600" />
                )}
              </div>
              <div>
                <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                <p className="text-sm text-gray-600">This action cannot be undone</p>
              </div>
            </div>
            <button
              onClick={onClose}
              className="text-gray-400 hover:text-gray-600 transition-colors"
              disabled={loading}
            >
              <XCircle className="h-6 w-6" />
            </button>
          </div>

          <div className="p-6">
            <p className="text-gray-700 mb-4">{message}</p>
            <div className="bg-gray-50 p-3 rounded-lg mb-4">
              <p className="text-sm text-gray-600">
                <strong>User:</strong> {userName}
              </p>
              <p className="text-sm text-gray-600">
                <strong>Action:</strong> {action === 'promote' ? 'Promote to Admin' : 'Remove Admin Rights'}
              </p>
            </div>

            <div className="flex justify-end space-x-3">
              <button
                onClick={onClose}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
                disabled={loading}
              >
                Cancel
              </button>
              <button
                onClick={onConfirm}
                className={`px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors ${
                  action === 'promote'
                    ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500'
                    : 'bg-red-600 hover:bg-red-700 focus:ring-red-500'
                }`}
                disabled={loading}
              >
                {loading ? 'Processing...' : (action === 'promote' ? 'Promote to Admin' : 'Remove Admin Rights')}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export function UsersGrid({ users, loading, onRefresh }: UsersGridProps) {
  const { currentUser } = useAuth();
  const { showSuccess, showError } = useNotification();
  const [searchTerm, setSearchTerm] = useState('');
  const [filterRole, setFilterRole] = useState<'all' | 'admin' | 'user'>('all');
  const [filterDepartment, setFilterDepartment] = useState('all');
  const [confirmationModal, setConfirmationModal] = useState<{
    isOpen: boolean;
    userId: string;
    userName: string;
    action: 'promote' | 'demote';
  }>({
    isOpen: false,
    userId: '',
    userName: '',
    action: 'promote'
  });
  const [processing, setProcessing] = useState<string | null>(null);

  // Define available departments
  const departments = ['CSE', 'ECE', 'IT', 'CIVIL', 'MECHANICAL', 'AERONAUTICAL', 'POLYTECHNIC'];

  // Filter users based on search term, role, and department
  const filteredUsers = users.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         user.mobile.includes(searchTerm);
    const matchesRole = filterRole === 'all' || user.role === filterRole;
    const matchesDepartment = filterDepartment === 'all' || user.department === filterDepartment;
    
    return matchesSearch && matchesRole && matchesDepartment;
  });

  const handlePromoteToAdmin = (userId: string, userName: string) => {
    setConfirmationModal({
      isOpen: true,
      userId,
      userName,
      action: 'promote'
    });
  };

  const handleRemoveAdmin = (userId: string, userName: string) => {
    setConfirmationModal({
      isOpen: true,
      userId,
      userName,
      action: 'demote'
    });
  };

  const handleConfirmAction = async () => {
    if (!confirmationModal.userId) return;

    try {
      setProcessing(confirmationModal.userId);
      
      if (confirmationModal.action === 'promote') {
        await FirestoreService.updateUser(confirmationModal.userId, { role: 'admin' });
        showSuccess('User Promoted', `${confirmationModal.userName} has been promoted to admin successfully`);
      } else {
        await FirestoreService.updateUser(confirmationModal.userId, { role: 'user' });
        showSuccess('Admin Rights Removed', `${confirmationModal.userName}'s admin rights have been removed successfully`);
      }

      onRefresh();
    } catch (error) {
      console.error('Error updating user role:', error);
      showError('Update Failed', `Failed to ${confirmationModal.action === 'promote' ? 'promote' : 'demote'} user. Please try again.`);
    } finally {
      setProcessing(null);
      setConfirmationModal({ isOpen: false, userId: '', userName: '', action: 'promote' });
    }
  };

  const canPromoteUser = (targetUser: UserType) => {
    return currentUser?.role === 'admin' && targetUser.role !== 'admin';
  };

  const canRemoveAdmin = (targetUser: UserType) => {
    return targetUser.role === 'admin' && currentUser?.email === 'karehallbooking@gmail.com';
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">Current Users</h1>
            <p className="text-gray-600">Manage and view all registered users</p>
          </div>
          <div className="flex items-center space-x-2 text-sm text-gray-600">
            <Users className="h-5 w-5" />
            <span>{filteredUsers.length} users</span>
          </div>
        </div>
      </div>

      {/* Search and Filters */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        <div className="flex flex-col md:flex-row gap-4">
          {/* Search */}
          <div className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search users by name, email, or mobile..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>

          {/* Role Filter */}
          <div className="md:w-48">
            <select
              value={filterRole}
              onChange={(e) => setFilterRole(e.target.value as 'all' | 'admin' | 'user')}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Users</option>
              <option value="admin">Admins</option>
              <option value="user">Regular Users</option>
            </select>
          </div>

          {/* Department Filter */}
          <div className="md:w-48">
            <select
              value={filterDepartment}
              onChange={(e) => setFilterDepartment(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Departments</option>
              {departments.map(dept => (
                <option key={dept} value={dept}>{dept}</option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Users Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        {filteredUsers.map((user) => (
          <div key={user.uid} className="bg-white rounded-lg shadow-md p-8 hover:shadow-lg transition-shadow min-h-[320px]">
            {/* Avatar and Name */}
            <div className="flex items-center space-x-4 mb-6">
              <div className={`w-16 h-16 rounded-full flex items-center justify-center ${
                user.role === 'admin' ? 'bg-red-100' : 'bg-blue-100'
              }`}>
                <Users className={`h-8 w-8 ${
                  user.role === 'admin' ? 'text-red-600' : 'text-blue-600'
                }`} />
              </div>
              <div className="flex-1 min-w-0">
                <h3 className="text-xl font-bold text-gray-900 mb-1 break-words">{user.name}</h3>
                <p className="text-base text-gray-600 break-words">{user.email}</p>
              </div>
            </div>

            {/* Contact Info */}
            <div className="space-y-3 mb-6">
              {user.mobile && (
                <div className="flex items-center text-base text-gray-700">
                  <Phone className="h-5 w-5 text-gray-500 mr-3" />
                  <span className="break-words">{user.mobile}</span>
                </div>
              )}
              <div className="flex items-center text-base text-gray-700">
                <Building className="h-5 w-5 text-gray-500 mr-3" />
                <span className="break-words">{user.department || 'Not specified'}</span>
              </div>
            </div>

            {/* Role Badge */}
            <div className="mb-6">
              <span className={`inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold ${
                user.role === 'admin' 
                  ? 'bg-red-100 text-red-800' 
                  : 'bg-green-100 text-green-800'
              }`}>
                {user.role === 'admin' ? (
                  <>
                    <Shield className="h-4 w-4 mr-2" />
                    Admin
                  </>
                ) : (
                  <>
                    <Users className="h-4 w-4 mr-2" />
                    User
                  </>
                )}
              </span>
            </div>

            {/* Additional Info */}
            {(user.designation || user.cabinNumber || user.officeLocation) && (
              <div className="space-y-2 mb-6 text-sm text-gray-600">
                {user.designation && (
                  <div className="flex items-center">
                    <GraduationCap className="h-4 w-4 mr-2" />
                    <span className="break-words">{user.designation}</span>
                  </div>
                )}
                {user.cabinNumber && (
                  <div className="flex items-center">
                    <Home className="h-4 w-4 mr-2" />
                    <span className="break-words">Cabin: {user.cabinNumber}</span>
                  </div>
                )}
                {user.officeLocation && (
                  <div className="flex items-center">
                    <MapPin className="h-4 w-4 mr-2" />
                    <span className="break-words">{user.officeLocation}</span>
                  </div>
                )}
              </div>
            )}

            {/* Action Buttons */}
            <div className="flex space-x-3 mt-auto">
              {canPromoteUser(user) && (
                <button
                  onClick={() => handlePromoteToAdmin(user.uid, user.name)}
                  disabled={processing === user.uid}
                  className="flex-1 flex items-center justify-center space-x-2 px-4 py-3 bg-blue-600 text-white text-base font-semibold rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50"
                >
                  <ShieldCheck className="h-5 w-5" />
                  <span>Make Admin</span>
                </button>
              )}
              
              {canRemoveAdmin(user) && (
                <button
                  onClick={() => handleRemoveAdmin(user.uid, user.name)}
                  disabled={processing === user.uid}
                  className="flex-1 flex items-center justify-center space-x-2 px-4 py-3 bg-red-600 text-white text-base font-semibold rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50"
                >
                  <Shield className="h-5 w-5" />
                  <span>Remove as Admin</span>
                </button>
              )}
            </div>
          </div>
        ))}
      </div>

      {/* Empty State */}
      {filteredUsers.length === 0 && (
        <div className="bg-white rounded-lg shadow-sm p-12 text-center">
          <Users className="h-16 w-16 text-gray-400 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-800 mb-2">No users found</h3>
          <p className="text-gray-500">
            {searchTerm || filterRole !== 'all' || filterDepartment !== 'all'
              ? 'Try adjusting your search or filters'
              : 'No users have been registered yet'
            }
          </p>
        </div>
      )}

      {/* Confirmation Modal */}
      <ConfirmationModal
        isOpen={confirmationModal.isOpen}
        onClose={() => setConfirmationModal({ isOpen: false, userId: '', userName: '', action: 'promote' })}
        onConfirm={handleConfirmAction}
        title={confirmationModal.action === 'promote' ? 'Promote to Admin' : 'Remove Admin Rights'}
        message={confirmationModal.action === 'promote' 
          ? `Are you sure you want to promote ${confirmationModal.userName} to admin? They will have full access to the admin panel.`
          : `Are you sure you want to remove admin rights from ${confirmationModal.userName}? They will lose access to the admin panel.`
        }
        action={confirmationModal.action}
        userName={confirmationModal.userName}
        loading={processing === confirmationModal.userId}
      />
    </div>
  );
}
