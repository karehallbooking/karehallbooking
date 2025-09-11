import React, { useState } from 'react';
import { AppNavbar } from './AppNavbar';
import { Sidebar } from './Sidebar';
import { Menu, X } from 'lucide-react';

interface DashboardLayoutProps {
  children: React.ReactNode;
}

export function DashboardLayout({ children }: DashboardLayoutProps) {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className="min-h-screen bg-muted">
      <AppNavbar showUserMenu={true} />
      
      {/* Mobile sidebar */}
      {sidebarOpen && (
        <div className="fixed inset-0 z-50 lg:hidden">
          <div className="fixed inset-0 bg-gray-600 bg-opacity-75" onClick={() => setSidebarOpen(false)} />
          <div className="fixed inset-y-0 left-0 flex w-64 flex-col bg-white">
            <div className="flex items-center justify-end px-4 py-3 border-b border-gray-200">
              <button
                onClick={() => setSidebarOpen(false)}
                className="text-gray-400 hover:text-gray-600"
              >
                <X className="h-6 w-6" />
              </button>
            </div>
            <div className="flex-1 overflow-y-auto">
              <Sidebar />
            </div>
          </div>
        </div>
      )}

      {/* Desktop layout with sidebar and content side by side */}
      <div className="flex h-screen pt-24">
        {/* Scrollable Sidebar - Desktop only */}
        <div className="hidden lg:block w-64 bg-white shadow-lg border-r border-gray-200 h-full">
          <Sidebar />
        </div>
        
        {/* Main content area */}
        <div className="flex-1 overflow-y-auto">
          {/* Mobile menu button */}
          <div className="lg:hidden fixed top-24 left-4 z-30">
            <button
              type="button"
              className="p-2 text-gray-700 bg-white rounded-md shadow-md"
              onClick={() => setSidebarOpen(true)}
            >
              <Menu className="h-6 w-6" />
            </button>
          </div>

          <main className="p-6">
            {children}
          </main>
        </div>
      </div>
    </div>
  );
}