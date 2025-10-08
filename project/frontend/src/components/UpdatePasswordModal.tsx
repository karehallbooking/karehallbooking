import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { updatePassword, EmailAuthProvider, linkWithCredential } from 'firebase/auth';
import { auth } from '../config/firebase';

interface UpdatePasswordModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

const UpdatePasswordModal: React.FC<UpdatePasswordModalProps> = ({
  isOpen,
  onClose,
  onSuccess
}) => {
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isUpdating, setIsUpdating] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleUpdatePassword = async () => {
    if (!newPassword.trim()) {
      setError('Please enter a new password');
      return;
    }

    if (newPassword.length < 6) {
      setError('Password must be at least 6 characters long');
      return;
    }

    if (newPassword !== confirmPassword) {
      setError('Passwords do not match');
      return;
    }

    setIsUpdating(true);
    setError('');
    setSuccess('');

    try {
      console.log('🔑 Adding password to Google account...');
      
      if (!auth.currentUser) {
        throw new Error('No user is currently signed in');
      }

      // Check if user already has a password
      const providerData = auth.currentUser.providerData;
      const hasPassword = providerData.some(provider => provider.providerId === 'password');
      
      if (hasPassword) {
        // User already has password, update it
        console.log('🔄 Updating existing password...');
        await updatePassword(auth.currentUser, newPassword);
        console.log('✅ Password updated successfully');
        setSuccess('Password updated successfully!');
      } else {
        // User doesn't have password, add it by linking
        console.log('🔗 Adding password provider to Google account...');
        const credential = EmailAuthProvider.credential(auth.currentUser.email || '', newPassword);
        await linkWithCredential(auth.currentUser, credential);
        console.log('✅ Password provider added successfully');
        setSuccess('Password added successfully! You can now login with both Google and email+password.');
      }

      // Clear form
      setNewPassword('');
      setConfirmPassword('');
      
      // Show success message for 2 seconds, then close
      setTimeout(() => {
        onSuccess();
        onClose();
      }, 2000);

    } catch (error: any) {
      console.error('❌ Error updating password:', error);
      
      if (error.code === 'auth/weak-password') {
        setError('Password is too weak. Please choose a stronger password.');
      } else if (error.code === 'auth/requires-recent-login') {
        setError('For security reasons, please sign out and sign in again before updating your password.');
      } else if (error.code === 'auth/provider-already-linked') {
        setError('Password is already linked to this account.');
      } else {
        setError(error.message || 'Failed to update password');
      }
    } finally {
      setIsUpdating(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-800">Update Password</h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700 text-2xl"
          >
            ×
          </button>
        </div>

        <div className="mb-4">
          <p className="text-gray-600 mb-4">
            Add a password to your account so you can login with both Google and email+password.
          </p>
        </div>

        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              New Password
            </label>
            <input
              type="password"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter new password"
              disabled={isUpdating}
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Confirm Password
            </label>
            <input
              type="password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Confirm new password"
              disabled={isUpdating}
            />
          </div>
        </div>

        {error && (
          <div className="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
            {error}
          </div>
        )}

        {success && (
          <div className="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
            {success}
          </div>
        )}

        <div className="flex space-x-3 mt-6">
          <button
            onClick={onClose}
            className="flex-1 px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors"
            disabled={isUpdating}
          >
            Cancel
          </button>
          <button
            onClick={handleUpdatePassword}
            disabled={isUpdating || !newPassword.trim() || !confirmPassword.trim()}
            className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {isUpdating ? 'Updating...' : 'Update Password'}
          </button>
        </div>

        <div className="mt-4 text-sm text-gray-500">
          <p>After adding a password, you'll be able to login with:</p>
          <ul className="list-disc list-inside mt-1">
            <li>Google Sign-in</li>
            <li>Email + Password</li>
          </ul>
        </div>
      </div>
    </div>
  );
};

export default UpdatePasswordModal;
