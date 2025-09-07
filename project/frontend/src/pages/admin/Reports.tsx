import React, { useState, useEffect } from 'react';
import { BarChart3, TrendingUp, Calendar, Building, Users, Download } from 'lucide-react';
import { FirestoreService } from '../../services/firestoreService';
import { Booking, Hall } from '../../types';
import { AdminLayout } from '../../components/AdminLayout';

interface ReportData {
  totalBookings: number;
  bookingsByStatus: {
    pending: number;
    approved: number;
    rejected: number;
  };
  bookingsByHall: { [hallName: string]: number };
  monthlyBookings: { [month: string]: number };
  topDepartments: { [department: string]: number };
}

export function Reports() {
  const [reportData, setReportData] = useState<ReportData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    generateReports();
  }, []);

  const generateReports = async () => {
    try {
      setLoading(true);
      
      // Fetch all data
      const [bookings, halls] = await Promise.all([
        FirestoreService.getAllBookings(),
        FirestoreService.getHalls()
      ]);

      // Calculate statistics
      const totalBookings = bookings.length;
      
      const bookingsByStatus = {
        pending: bookings.filter(b => b.status === 'pending').length,
        approved: bookings.filter(b => b.status === 'approved').length,
        rejected: bookings.filter(b => b.status === 'rejected').length
      };

      // Bookings by hall
      const bookingsByHall: { [hallName: string]: number } = {};
      halls.forEach(hall => {
        bookingsByHall[hall.name] = bookings.filter(b => b.hallName === hall.name).length;
      });

      // Monthly bookings (last 6 months)
      const monthlyBookings: { [month: string]: number } = {};
      const last6Months = Array.from({ length: 6 }, (_, i) => {
        const date = new Date();
        date.setMonth(date.getMonth() - i);
        return date.toISOString().slice(0, 7); // YYYY-MM format
      });

      last6Months.forEach(month => {
        monthlyBookings[month] = bookings.filter(b => 
          b.createdAt.startsWith(month)
        ).length;
      });

      // Top departments
      const topDepartments: { [department: string]: number } = {};
      bookings.forEach(booking => {
        topDepartments[booking.department] = (topDepartments[booking.department] || 0) + 1;
      });

      setReportData({
        totalBookings,
        bookingsByStatus,
        bookingsByHall,
        monthlyBookings,
        topDepartments
      });
    } catch (error) {
      console.error('Error generating reports:', error);
    } finally {
      setLoading(false);
    }
  };

  const exportReport = () => {
    if (!reportData) return;
    
    const csvContent = [
      ['Metric', 'Value'],
      ['Total Bookings', reportData.totalBookings],
      ['Pending Bookings', reportData.bookingsByStatus.pending],
      ['Approved Bookings', reportData.bookingsByStatus.approved],
      ['Rejected Bookings', reportData.bookingsByStatus.rejected],
      ['', ''],
      ['Hall', 'Bookings'],
      ...Object.entries(reportData.bookingsByHall).map(([hall, count]) => [hall, count]),
      ['', ''],
      ['Department', 'Bookings'],
      ...Object.entries(reportData.topDepartments).map(([dept, count]) => [dept, count])
    ].map(row => row.join(',')).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `hall-booking-report-${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    window.URL.revokeObjectURL(url);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (!reportData) {
    return (
      <div className="bg-white rounded-lg shadow-sm p-12 text-center">
        <BarChart3 className="h-16 w-16 text-gray-400 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-800 mb-2">No data available</h3>
        <p className="text-gray-500">Generate reports to view analytics</p>
      </div>
    );
  }

  return (
    <AdminLayout>
      <div className="space-y-6">
      <div className="bg-white rounded-lg shadow-sm p-6">
        <div className="flex justify-between items-center">
          <div>
            <h1 className="text-2xl font-bold text-gray-800 mb-2">Reports & Analytics</h1>
            <p className="text-gray-600">Comprehensive overview of hall booking statistics</p>
          </div>
          <button
            onClick={exportReport}
            className="flex items-center space-x-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <Download className="h-4 w-4" />
            <span>Export Report</span>
          </button>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="p-2 bg-blue-100 rounded-lg">
              <Calendar className="h-6 w-6 text-blue-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Total Bookings</p>
              <p className="text-2xl font-bold text-gray-900">{reportData.totalBookings}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="p-2 bg-green-100 rounded-lg">
              <TrendingUp className="h-6 w-6 text-green-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Approved</p>
              <p className="text-2xl font-bold text-gray-900">{reportData.bookingsByStatus.approved}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="p-2 bg-yellow-100 rounded-lg">
              <Users className="h-6 w-6 text-yellow-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Pending</p>
              <p className="text-2xl font-bold text-gray-900">{reportData.bookingsByStatus.pending}</p>
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-sm p-6">
          <div className="flex items-center">
            <div className="p-2 bg-red-100 rounded-lg">
              <Building className="h-6 w-6 text-red-600" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600">Rejected</p>
              <p className="text-2xl font-bold text-gray-900">{reportData.bookingsByStatus.rejected}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Bookings by Hall */}
        <div className="bg-white rounded-lg shadow-sm p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Bookings by Hall</h3>
          <div className="space-y-3">
            {Object.entries(reportData.bookingsByHall).map(([hallName, count]) => (
              <div key={hallName} className="flex items-center justify-between">
                <span className="text-sm text-gray-600">{hallName}</span>
                <div className="flex items-center space-x-2">
                  <div className="w-32 bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-blue-600 h-2 rounded-full"
                      style={{ width: `${(count / Math.max(...Object.values(reportData.bookingsByHall))) * 100}%` }}
                    ></div>
                  </div>
                  <span className="text-sm font-medium text-gray-900 w-8">{count}</span>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Top Departments */}
        <div className="bg-white rounded-lg shadow-sm p-6">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">Top Departments</h3>
          <div className="space-y-3">
            {Object.entries(reportData.topDepartments)
              .sort(([,a], [,b]) => b - a)
              .slice(0, 5)
              .map(([department, count]) => (
                <div key={department} className="flex items-center justify-between">
                  <span className="text-sm text-gray-600">{department}</span>
                  <div className="flex items-center space-x-2">
                    <div className="w-32 bg-gray-200 rounded-full h-2">
                      <div
                        className="bg-green-600 h-2 rounded-full"
                        style={{ width: `${(count / Math.max(...Object.values(reportData.topDepartments))) * 100}%` }}
                      ></div>
                    </div>
                    <span className="text-sm font-medium text-gray-900 w-8">{count}</span>
                  </div>
                </div>
              ))}
          </div>
        </div>
      </div>

      {/* Monthly Trends */}
      <div className="bg-white rounded-lg shadow-sm p-6">
        <h3 className="text-lg font-semibold text-gray-800 mb-4">Monthly Booking Trends</h3>
        <div className="space-y-3">
          {Object.entries(reportData.monthlyBookings)
            .sort(([a], [b]) => a.localeCompare(b))
            .map(([month, count]) => (
              <div key={month} className="flex items-center justify-between">
                <span className="text-sm text-gray-600">
                  {new Date(month + '-01').toLocaleDateString('en-US', { year: 'numeric', month: 'long' })}
                </span>
                <div className="flex items-center space-x-2">
                  <div className="w-48 bg-gray-200 rounded-full h-2">
                    <div
                      className="bg-purple-600 h-2 rounded-full"
                      style={{ width: `${(count / Math.max(...Object.values(reportData.monthlyBookings))) * 100}%` }}
                    ></div>
                  </div>
                  <span className="text-sm font-medium text-gray-900 w-8">{count}</span>
                </div>
              </div>
            ))}
        </div>
      </div>
      </div>
    </AdminLayout>
  );
}
