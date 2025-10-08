import React, { useState } from 'react';
import { useAuth } from '../contexts/AuthContext';
import { auth } from '../config/firebase';
import { fetchSignInMethodsForEmail, signInWithEmailAndPassword, signInWithPopup, GoogleAuthProvider, linkWithPopup } from 'firebase/auth';

const DebugAccountLinking: React.FC = () => {
  const [email, setEmail] = useState('projectify198@gmail.com');
  const [password, setPassword] = useState('');
  const [debugInfo, setDebugInfo] = useState('');
  const [isDebugging, setIsDebugging] = useState(false);
  
  const { linkGoogleToExistingAccount } = useAuth();

  const debugAccountStatus = async () => {
    setIsDebugging(true);
    setDebugInfo('');

    try {
      let info = '🔍 ACCOUNT DEBUGGING REPORT\n\n';
      
      // 1. Check Firebase sign-in methods
      info += '1. Firebase Sign-in Methods:\n';
      const signInMethods = await fetchSignInMethodsForEmail(auth, email);
      info += `   Methods: ${signInMethods.join(', ')}\n`;
      info += `   Has Password: ${signInMethods.includes('password')}\n`;
      info += `   Has Google: ${signInMethods.includes('google.com')}\n\n`;
      
      // 2. Try to sign in with email/password
      info += '2. Email/Password Sign-in Test:\n';
      try {
        const emailResult = await signInWithEmailAndPassword(auth, email, password);
        info += `   ✅ SUCCESS - UID: ${emailResult.user.uid}\n`;
        info += `   Providers: ${emailResult.user.providerData.map(p => p.providerId).join(', ')}\n`;
        
        // Check if Google is already linked
        const hasGoogle = emailResult.user.providerData.some(p => p.providerId === 'google.com');
        info += `   Has Google Linked: ${hasGoogle}\n\n`;
        
        // 3. Try to link Google
        if (!hasGoogle) {
          info += '3. Attempting Google Link:\n';
          try {
            const provider = new GoogleAuthProvider();
            const linkResult = await linkWithPopup(emailResult.user, provider);
            info += `   ✅ Google Link SUCCESS!\n`;
            info += `   Final Providers: ${linkResult.user.providerData.map(p => p.providerId).join(', ')}\n`;
          } catch (linkError: any) {
            info += `   ❌ Google Link FAILED: ${linkError.message}\n`;
            info += `   Error Code: ${linkError.code}\n`;
          }
        } else {
          info += '3. Google already linked - no action needed\n';
        }
        
        // Sign out
        await auth.signOut();
        
      } catch (emailError: any) {
        info += `   ❌ FAILED: ${emailError.message}\n`;
        info += `   Error Code: ${emailError.code}\n\n`;
      }
      
      // 4. Try Google sign-in
      info += '4. Google Sign-in Test:\n';
      try {
        const provider = new GoogleAuthProvider();
        const googleResult = await signInWithPopup(auth, provider);
        info += `   ✅ SUCCESS - UID: ${googleResult.user.uid}\n`;
        info += `   Providers: ${googleResult.user.providerData.map(p => p.providerId).join(', ')}\n`;
        
        // Sign out
        await auth.signOut();
        
      } catch (googleError: any) {
        info += `   ❌ FAILED: ${googleError.message}\n`;
        info += `   Error Code: ${googleError.code}\n`;
      }
      
      setDebugInfo(info);
      
    } catch (error: any) {
      setDebugInfo(`❌ Debug Error: ${error.message}`);
    } finally {
      setIsDebugging(false);
    }
  };

  const testOurLinkingFunction = async () => {
    setIsDebugging(true);
    setDebugInfo('');

    try {
      let info = '🧪 TESTING OUR LINKING FUNCTION\n\n';
      
      const user = await linkGoogleToExistingAccount(email, password);
      info += `✅ Linking Function SUCCESS!\n`;
      info += `User: ${user.name}\n`;
      info += `Email: ${user.email}\n`;
      info += `UID: ${user.uid}\n`;
      info += `Role: ${user.role}\n`;
      
      // Check current user providers
      if (auth.currentUser) {
        info += `Current Providers: ${auth.currentUser.providerData.map(p => p.providerId).join(', ')}\n`;
      }
      
      setDebugInfo(info);
      
    } catch (error: any) {
      setDebugInfo(`❌ Linking Function FAILED: ${error.message}\nError Code: ${error.code || 'Unknown'}`);
    } finally {
      setIsDebugging(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-100 py-8">
      <div className="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 className="text-3xl font-bold text-gray-800 mb-6">Account Linking Debug Tool</h1>
        
        <div className="space-y-4 mb-6">
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

          <div className="flex space-x-4">
            <button
              onClick={debugAccountStatus}
              disabled={isDebugging || !email}
              className="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isDebugging ? 'Debugging...' : 'Debug Account Status'}
            </button>
            
            <button
              onClick={testOurLinkingFunction}
              disabled={isDebugging || !email || !password}
              className="bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {isDebugging ? 'Testing...' : 'Test Our Linking Function'}
            </button>
          </div>
        </div>

        {debugInfo && (
          <div className="p-4 bg-gray-50 border border-gray-200 rounded whitespace-pre-line font-mono text-sm">
            {debugInfo}
          </div>
        )}

        <div className="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded">
          <h3 className="font-semibold text-yellow-800 mb-2">Debug Instructions:</h3>
          <ol className="list-decimal list-inside text-yellow-700 space-y-1">
            <li>Enter your email and password</li>
            <li>Click "Debug Account Status" to see current state</li>
            <li>Click "Test Our Linking Function" to test the linking</li>
            <li>Check the console for detailed logs</li>
            <li>Check Firebase Console to see provider changes</li>
          </ol>
        </div>
      </div>
    </div>
  );
};

export default DebugAccountLinking;
