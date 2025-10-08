import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';

/**
 * Test component for account linking functionality
 * This component can be used to test the account linking features
 */
export const AccountLinkingTest: React.FC = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  
  const { 
    login, 
    loginWithGoogle, 
    linkGoogleAccount, 
    linkEmailPasswordAccount 
  } = useAuth();

  const testEmailPasswordLogin = async () => {
    setLoading(true);
    setError('');
    setMessage('');

    try {
      const user = await login(email, password);
      setMessage(`✅ Email/Password login successful! UID: ${user.uid}`);
    } catch (err: any) {
      setError(`❌ Email/Password login failed: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const testGoogleLogin = async () => {
    setLoading(true);
    setError('');
    setMessage('');

    try {
      const user = await loginWithGoogle();
      setMessage(`✅ Google login successful! UID: ${user.uid}`);
    } catch (err: any) {
      if (err.message === 'ACCOUNT_EXISTS_WITH_PASSWORD') {
        setMessage('🔄 Account exists with password. You can link Google account after email/password login.');
      } else {
        setError(`❌ Google login failed: ${err.message}`);
      }
    } finally {
      setLoading(false);
    }
  };

  const testLinkGoogle = async () => {
    setLoading(true);
    setError('');
    setMessage('');

    try {
      await linkGoogleAccount();
      setMessage('✅ Google account linked successfully!');
    } catch (err: any) {
      setError(`❌ Failed to link Google account: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  const testLinkEmailPassword = async () => {
    if (!email || !password) {
      setError('Please enter email and password');
      return;
    }

    setLoading(true);
    setError('');
    setMessage('');

    try {
      await linkEmailPasswordAccount(email, password);
      setMessage('✅ Email/Password account linked successfully!');
    } catch (err: any) {
      setError(`❌ Failed to link email/password account: ${err.message}`);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto p-6 bg-white rounded-lg shadow-lg">
      <h2 className="text-2xl font-bold mb-6 text-gray-800">Account Linking Test</h2>
      
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Email Address
          </label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter email address"
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Password
          </label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter password"
          />
        </div>

        <div className="grid grid-cols-2 gap-4">
          <button
            onClick={testEmailPasswordLogin}
            disabled={loading || !email || !password}
            className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Test Email/Password Login
          </button>

          <button
            onClick={testGoogleLogin}
            disabled={loading}
            className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Test Google Login
          </button>

          <button
            onClick={testLinkGoogle}
            disabled={loading}
            className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Link Google Account
          </button>

          <button
            onClick={testLinkEmailPassword}
            disabled={loading || !email || !password}
            className="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Link Email/Password
          </button>
        </div>

        {message && (
          <div className="p-3 bg-green-50 border border-green-200 rounded-md">
            <p className="text-sm text-green-800">{message}</p>
          </div>
        )}

        {error && (
          <div className="p-3 bg-red-50 border border-red-200 rounded-md">
            <p className="text-sm text-red-800">{error}</p>
          </div>
        )}

        <div className="mt-6 p-4 bg-gray-50 rounded-md">
          <h3 className="text-sm font-medium text-gray-700 mb-2">Test Instructions:</h3>
          <ol className="text-sm text-gray-600 space-y-1">
            <li>1. First, register with email/password using the main login page</li>
            <li>2. Then try Google login with the same email - should show linking modal</li>
            <li>3. Or register with Google first, then try email/password login</li>
            <li>4. After linking, both methods should work and return the same UID</li>
          </ol>
        </div>
      </div>
    </div>
  );
};






