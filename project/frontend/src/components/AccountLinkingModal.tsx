import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';

interface AccountLinkingModalProps {
  isOpen: boolean;
  onClose: () => void;
  email: string;
  onSuccess: () => void;
}

const AccountLinkingModal: React.FC<AccountLinkingModalProps> = ({
  isOpen,
  onClose,
  email,
  onSuccess
}) => {
  const [password, setPassword] = useState('');
  const [isLinking, setIsLinking] = useState(false);
  const [error, setError] = useState('');
  const { linkGoogleToExistingAccount } = useAuth();

  const handleLinkAccount = async () => {
    if (!password.trim()) {
      setError('Please enter your password');
      return;
    }

    setIsLinking(true);
    setError('');

    try {
      console.log('🔗 Attempting to link Google account to existing email/password account...');
      await linkGoogleToExistingAccount(email, password);
      console.log('✅ Account linking successful!');
      onSuccess();
      onClose();
    } catch (error: any) {
      console.error('❌ Account linking failed:', error);
      setError(error.message || 'Failed to link accounts');
    } finally {
      setIsLinking(false);
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-800">Link Your Accounts</h2>
          <button
            onClick={onClose}
            className="text-gray-500 hover:text-gray-700 text-2xl"
          >
            ×
          </button>
        </div>

        <div className="mb-4">
          <p className="text-gray-600 mb-2">
            An account with email <strong>{email}</strong> already exists with email/password login.
          </p>
          <p className="text-gray-600 mb-4">
            To link your Google account, please enter your password below:
          </p>
        </div>

        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Password
          </label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Enter your password"
            disabled={isLinking}
          />
        </div>

        {error && (
          <div className="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
            {error}
          </div>
        )}

        <div className="flex space-x-3">
          <button
            onClick={onClose}
            className="flex-1 px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition-colors"
            disabled={isLinking}
          >
            Cancel
          </button>
          <button
            onClick={handleLinkAccount}
            disabled={isLinking || !password.trim()}
            className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            {isLinking ? 'Linking...' : 'Link Accounts'}
          </button>
        </div>

        <div className="mt-4 text-sm text-gray-500">
          <p>After linking, you'll be able to login with both:</p>
          <ul className="list-disc list-inside mt-1">
            <li>Email + Password</li>
            <li>Google Sign-in</li>
          </ul>
        </div>
      </div>
    </div>
  );
};

export default AccountLinkingModal;