import React, { useState } from 'react';
import { setupAdminUser } from '../utils/setupAdmin';
import { updateUserToAdmin } from '../utils/adminUtils';
import { signInWithEmailAndPassword } from 'firebase/auth';
import { auth } from '../config/firebase';

export function SetupAdmin() {
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');

  const handleSetupAdmin = async () => {
    setLoading(true);
    setMessage('');
    setError('');

    try {
      // First try to create the admin user
      await setupAdminUser();
      setMessage('Admin user created successfully! You can now login with:\nEmail: karehallbooking@gmail.com\nPassword: Karehallbooking@198');
    } catch (err: any) {
      if (err.message.includes('email-already-in-use')) {
        // User exists, try to update to admin role
        try {
          const userCredential = await signInWithEmailAndPassword(auth, 'karehallbooking@gmail.com', 'Karehallbooking@198');
          await updateUserToAdmin(userCredential.user.uid);
          setMessage('Admin user updated successfully! You can now login with:\nEmail: karehallbooking@gmail.com\nPassword: Karehallbooking@198');
        } catch (updateErr: any) {
          setError(updateErr.message || 'Failed to update existing user to admin');
        }
      } else {
        setError(err.message || 'Failed to create admin user');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center px-4">
      <div className="max-w-md w-full bg-white rounded-lg shadow-md p-6">
        <div className="text-center mb-6">
          <h2 className="text-2xl font-bold text-gray-800 mb-2">Setup Admin User</h2>
          <p className="text-gray-600">Create the initial admin user for KARE Hall Booking System</p>
        </div>

        <div className="space-y-4">
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 className="font-medium text-blue-800 mb-2">Admin Credentials:</h3>
            <p className="text-sm text-blue-700">
              <strong>Email:</strong> karehallbooking@gmail.com<br />
              <strong>Password:</strong> Karehallbooking@198
            </p>
          </div>

          {message && (
            <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm whitespace-pre-line">
              {message}
            </div>
          )}

          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
              {error}
            </div>
          )}

          <button
            onClick={handleSetupAdmin}
            disabled={loading}
            className="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {loading ? 'Creating Admin User...' : 'Create Admin User'}
          </button>

          <div className="text-center">
            <a 
              href="/" 
              className="text-sm text-blue-600 hover:text-blue-800"
            >
              ← Back to Home
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
