import React, { useState } from 'react';
import { fetchSignInMethodsForEmail } from 'firebase/auth';
import { auth } from '../config/firebase';
import { Link, useNavigate } from 'react-router-dom';
import { Mail, Lock, School, Eye, EyeOff } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import { AppNavbar } from '../components/AppNavbar';
import AccountLinkingModal from '../components/AccountLinkingModal';

export function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);
  const [googleLoading, setGoogleLoading] = useState(false);
  const [error, setError] = useState('');
  const [linkingModal, setLinkingModal] = useState<{
    isOpen: boolean;
    email: string;
    existingMethod: 'password' | 'google';
  }>({
    isOpen: false,
    email: '',
    existingMethod: 'password'
  });
  const { login, loginWithGoogle } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const user = await login(email, password);
      console.log('✅ Login successful, user:', user);
      // Wait a bit for the auth state to update, then navigate
      setTimeout(() => {
        console.log('🔄 Navigating to dashboard based on user role:', user.role);
        navigate(user.role === 'admin' ? '/admin/dashboard' : '/dashboard');
      }, 1000);
    } catch (err: any) {
      try {
        // Detect if this email is registered only with Google (no password yet)
        const methods = await fetchSignInMethodsForEmail(auth, email);
        const hasGoogle = methods.includes('google.com');
        const hasPassword = methods.includes('password');

        if (hasGoogle && !hasPassword) {
          // Sign in with Google first, then open modal to link password to the same UID
          try {
            const user = await loginWithGoogle();
            console.log('✅ Signed in with Google to prepare linking for email/password', user?.uid);
            setLinkingModal({ isOpen: true, email, existingMethod: 'google' });
          } catch (googleErr: any) {
            setError(googleErr.message || 'Please sign in with Google first, then link your password.');
          }
          return;
        }
      } catch (_) {
        // fall back to original error if fetchSignInMethodsForEmail fails
      }

      setError(err.message || 'Login failed');
    } finally {
      setLoading(false);
    }
  };

  const handleGoogleLogin = async () => {
    setGoogleLoading(true);
    setError('');

    try {
      const user = await loginWithGoogle();
      console.log('✅ Google login successful, user:', user);
      // Wait a bit for the auth state to update, then navigate
      setTimeout(() => {
        console.log('🔄 Navigating to dashboard based on user role:', user.role);
        navigate(user.role === 'admin' ? '/admin/dashboard' : '/dashboard');
      }, 1000);
    } catch (err: any) {
      console.error('❌ Google login error:', err);
      
      // Check if this is an account linking error
      if (err.message.includes('Account linking required!')) {
        // Extract email from error message
        const emailMatch = err.message.match(/email ([^\s]+)/);
        const email = emailMatch ? emailMatch[1] : '';
        
        if (email) {
          setLinkingModal({
            isOpen: true,
            email: email,
            existingMethod: 'password'
          });
        } else {
          setError(err.message);
        }
      } else if (err.message === 'ACCOUNT_EXISTS_WITH_PASSWORD') {
        // Show account linking modal
        setLinkingModal({
          isOpen: true,
          email: email || 'this email',
          existingMethod: 'password'
        });
      } else {
        setError(err.message);
      }
    } finally {
      setGoogleLoading(false);
    }
  };

  const handleLinkingSuccess = () => {
    setLinkingModal({ isOpen: false, email: '', existingMethod: 'password' });
    // Reload the page to refresh the auth state
    window.location.reload();
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-primary to-primary-dark">
      <AppNavbar />

      {/* Main Content */}
      <div className="flex items-center justify-center min-h-screen px-4 pt-24">
        <div className="max-w-md w-full bg-white rounded-2xl shadow-2xl p-8">
        <div className="text-center mb-8">
          <School className="h-12 w-12 text-primary mx-auto mb-4" />
          <h2 className="text-3xl font-bold text-gray-800 mb-2">Welcome Back</h2>
          <p className="text-gray-600">Sign in to your KARE Hall Booking account</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
              {error}
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Email Address
            </label>
            <div className="relative">
              <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                className="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="your.email@kare.edu"
                required
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Password
            </label>
            <div className="relative">
              <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
              <input
                type={showPassword ? "text" : "password"}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                className="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="Enter your password"
                required
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none"
              >
                {showPassword ? (
                  <EyeOff className="h-5 w-5" />
                ) : (
                  <Eye className="h-5 w-5" />
                )}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading || googleLoading}
            className="w-full bg-primary text-white py-3 rounded-lg hover:bg-primary-dark transition-colors disabled:opacity-50 font-medium"
          >
            {loading ? 'Signing In...' : 'Sign In'}
          </button>
        </form>

        {/* Divider */}
        <div className="relative my-6">
          <div className="absolute inset-0 flex items-center">
            <div className="w-full border-t border-gray-300" />
          </div>
          <div className="relative flex justify-center text-sm">
            <span className="px-2 bg-white text-gray-500">Or continue with</span>
          </div>
        </div>

        {/* Google Sign-In Button */}
        <button
          onClick={handleGoogleLogin}
          disabled={loading || googleLoading}
          className="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50 font-medium"
        >
          <svg className="w-5 h-5 mr-3" viewBox="0 0 24 24">
            <path
              fill="#4285F4"
              d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
              fill="#34A853"
              d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
              fill="#FBBC05"
              d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
            />
            <path
              fill="#EA4335"
              d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
            />
          </svg>
          {googleLoading ? 'Signing in with Google...' : 'Sign in with Google'}
        </button>

        <div className="mt-6 text-center">
          <p className="text-gray-600">
            Don't have an account?{' '}
            <Link to="/register" className="text-primary hover:text-primary-dark font-medium">
              Create one here
            </Link>
          </p>
        </div>

        <div className="mt-6 text-center">
          <Link to="/" className="text-sm text-gray-500 hover:text-gray-700">
            ← Back to Home
          </Link>
        </div>
        </div>
      </div>

      {/* Account Linking Modal */}
      <AccountLinkingModal
        isOpen={linkingModal.isOpen}
        onClose={() => setLinkingModal({ isOpen: false, email: '', existingMethod: 'password' })}
        onSuccess={handleLinkingSuccess}
        email={linkingModal.email}
        existingMethod={linkingModal.existingMethod}
      />
    </div>
  );
}