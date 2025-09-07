import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { School, User, LogOut } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

interface AppNavbarProps {
  showLoginButton?: boolean;
  showUserMenu?: boolean;
  isAdminLayout?: boolean;
}

export function AppNavbar({ showLoginButton = false, showUserMenu = false, isAdminLayout = false }: AppNavbarProps) {
  const { currentUser, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      await logout();
      navigate('/');
    } catch (error) {
      console.error('Logout error:', error);
    }
  };

  return (
    <nav className="fixed top-0 left-0 right-0 z-50 bg-white shadow-lg border-l-4 border-primary">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-24 relative">
          {/* Left side - Logo and Title (3-4px gap from center) */}
          <div className="flex items-center space-x-2 flex-shrink-0" style={{ marginRight: '3px' }}>
            <School className="h-8 w-8 text-primary" />
            <span className="text-xl font-bold text-primary">KARE Hall Booking</span>
          </div>

          {/* Center - College Logo and Owner Image */}
          {/* Full Banner Image (always visible) */}
          <div className="absolute left-1/2 transform -translate-x-1/2 z-10 pointer-events-none">
            <img
              src="/src/assets/owner.png"
              alt="KALASALINGAM ACADEMY OF RESEARCH AND EDUCATION Banner"
              className="h-22 w-auto object-contain pointer-events-none"
              onError={(e) => {
                // Fallback to a simple text version if image fails to load
                e.currentTarget.style.display = 'none';
                const fallback = document.createElement('div');
                fallback.className = 'text-center bg-white p-4 rounded-lg shadow-md';
                fallback.innerHTML = `
                  <div class="text-lg font-bold text-blue-600">KALASALINGAM</div>
                  <div class="text-sm font-bold text-red-600">ACADEMY OF RESEARCH AND EDUCATION</div>
                  <div class="text-sm font-bold text-blue-600">(DEEMED TO BE UNIVERSITY)</div>
                  <div class="text-xs text-gray-600">Under sec. 3 of UGC Act 1956. Accredited by NAAC with "A++" Grade</div>
                `;
                e.currentTarget.parentNode?.appendChild(fallback);
              }}
            />
          </div>

          {/* Right side - User Menu or Login Button (aligned to far right) */}
          <div className="flex items-center space-x-4 flex-shrink-0" style={{ marginLeft: 'auto' }}>
            {showUserMenu && currentUser ? (
              <div className="flex items-center space-x-3">
                <div className="flex items-center space-x-2">
                  <User className="h-5 w-5 text-gray-600" />
                  <span className="text-gray-700 font-medium">{currentUser.name}</span>
                </div>
                <button
                  onClick={handleLogout}
                  className="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-bold"
                >
                  <LogOut className="h-5 w-5" />
                  <span>Logout</span>
                </button>
              </div>
            ) : showLoginButton ? (
              <Link
                to="/login"
                className="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors font-medium"
              >
                Login
              </Link>
            ) : (
              <div className="w-20"></div>
            )}
          </div>
        </div>
      </div>
    </nav>
  );
}
