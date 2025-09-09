import { useNavigate } from 'react-router-dom';
import { LogOut } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';

interface AppNavbarProps {
  showLoginButton?: boolean;
  showUserMenu?: boolean;
}

export function AppNavbar({ showLoginButton = false, showUserMenu = false }: AppNavbarProps) {
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
    <nav className="fixed top-0 left-0 right-0 z-50 bg-white border-b border-gray-200 pointer-events-none">
      {/* Increased height navbar */}
      <div className="flex items-center justify-between h-24">
        {/* Left: Logo with 0.5cm gap on left, top and bottom; image fits navbar height; not clickable */}
        <div className="flex items-center pl-[0.5cm] pr-0 py-[0.5cm]" style={{ marginLeft: 0 }}>
          <img
            src="/Number%20Systems%20(7).png"
            alt="KARE"
            className="h-full max-h-full w-auto object-contain select-none pointer-events-none"
            draggable={false}
          />
        </div>

        {/* Center: Title vertically and horizontally centered */}
        <div className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center">
          <span className="text-2xl font-bold text-primary">KARE Hall Booking</span>
        </div>

        {/* Right: Logout only (clickable) */}
        <div className="flex items-center gap-4 ml-auto pr-4 pointer-events-auto">
          {showUserMenu && currentUser ? (
            <button
              onClick={handleLogout}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            >
              <div className="flex items-center gap-2">
                <LogOut className="h-4 w-4" />
                <span>Logout</span>
              </div>
            </button>
          ) : showLoginButton ? (
            <a href="/login" className="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-dark transition-colors">
              Login
            </a>
          ) : (
            <div className="w-4" />
          )}
        </div>
      </div>
    </nav>
  );
}
