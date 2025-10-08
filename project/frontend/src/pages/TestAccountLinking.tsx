import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';

const TestAccountLinking: React.FC = () => {
  const [email, setEmail] = useState('projectify198@gmail.com');
  const [password, setPassword] = useState('');
  const [isLinking, setIsLinking] = useState(false);
  const [result, setResult] = useState('');
  const [error, setError] = useState('');
  
  const { linkGoogleToExistingAccount } = useAuth();

  const handleTestLinking = async () => {
    if (!email || !password) {
      setError('Please enter both email and password');
      return;
    }

    setIsLinking(true);
    setError('');
    setResult('');

    try {
      console.log('🧪 Testing account linking for:', email);
      const user = await linkGoogleToExistingAccount(email, password);
      console.log('✅ Account linking successful!', user);
      
      setResult(`✅ SUCCESS! Account linked successfully.
User: ${user.name}
Email: ${user.email}
UID: ${user.uid}
Role: ${user.role}
Linked Providers: ${user.linkedProviders?.join(', ') || 'email, google'}`);
      
    } catch (error: any) {
      console.error('❌ Account linking failed:', error);
      setError(`❌ FAILED: ${error.message}`);
    } finally {
      setIsLinking(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 py-8">
      <div className="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 className="text-3xl font-bold text-gray-800 mb-6">Account Linking Test</h1>
        
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
              placeholder="Enter email"
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

          <button
            onClick={handleTestLinking}
            disabled={isLinking || !email || !password}
            className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {isLinking ? 'Linking Accounts...' : 'Test Account Linking'}
          </button>

          {error && (
            <div className="p-3 bg-red-100 border border-red-400 text-red-700 rounded">
              {error}
            </div>
          )}

          {result && (
            <div className="p-3 bg-green-100 border border-green-400 text-green-700 rounded whitespace-pre-line">
              {result}
            </div>
          )}
        </div>

        <div className="mt-8 p-4 bg-blue-50 border border-blue-200 rounded">
          <h3 className="font-semibold text-blue-800 mb-2">How to Test:</h3>
          <ol className="list-decimal list-inside text-blue-700 space-y-1">
            <li>Enter the email and password for an existing email/password account</li>
            <li>Click "Test Account Linking"</li>
            <li>Complete the Google authentication popup</li>
            <li>Check Firebase Console to see both providers linked</li>
            <li>Try logging in with both email+password AND Google</li>
          </ol>
        </div>
      </div>
    </div>
  );
};

export default TestAccountLinking;
