import React, { useState, useEffect } from 'react';
import { BarChart3, TrendingUp, Calendar, Building, Users, Download } from 'lucide-react';
import { FirestoreService } from '../../services/firestoreService';
import { Booking, Hall, User } from '../../types';
import { AdminLayout } from '../../components/AdminLayout';
import ExcelJS from 'exceljs';
import { saveAs } from 'file-saver';

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

  const exportReport = async () => {
    try {
      // Fetch full real data
      const [bookings, users, halls] = await Promise.all([
        FirestoreService.getAllBookings(),
        FirestoreService.getAllUsers(),
        FirestoreService.getHalls()
      ]);

      // Derive sections
      const todayISO = new Date().toISOString().split('T')[0];
      const isFuture = (d: string) => d >= todayISO;
      const upcoming: Booking[] = bookings.filter(b => (b.dates || []).some(isFuture));
      const today = new Date();
      const inTenDays = new Date();
      inTenDays.setDate(today.getDate() + 10);
      const isWithinNextTenDays = (d: string) => {
        const dt = new Date(d);
        dt.setHours(0,0,0,0);
        const t0 = new Date(today);
        t0.setHours(0,0,0,0);
        const t1 = new Date(inTenDays);
        t1.setHours(0,0,0,0);
        return dt.getTime() >= t0.getTime() && dt.getTime() <= t1.getTime();
      };
      const approved: Booking[] = bookings.filter(b => b.status === 'approved');
      const pending: Booking[] = bookings.filter(b => b.status === 'pending');
      const rejected: Booking[] = bookings.filter(b => b.status === 'rejected');
      const cancelled: Booking[] = bookings.filter(b =>
        (b as any).status === 'cancelled' || (b as any).status === 'canceled' || (b as any).cancelled === true
      );

      // Monthly trends for last 12 months
      const months: string[] = Array.from({ length: 12 }, (_, i) => {
        const d = new Date();
        d.setMonth(d.getMonth() - (11 - i));
        return d.toISOString().slice(0, 7);
      });
      const monthlyCounts: number[] = months.map(m =>
        bookings.filter(b => String(b.createdAt).slice(0, 7) === m).length
      );

      // Aggregations
      const byHall: Record<string, number> = {};
      halls.forEach(h => {
        byHall[h.name] = bookings.filter(b => b.hallName === h.name).length;
      });

      const byDept: Record<string, number> = {};
      bookings.forEach(b => {
        const dept = b.department || b.userDepartment || 'Unknown';
        byDept[dept] = (byDept[dept] || 0) + 1;
      });

      // Colors
      const COLORS = {
        headerBg: 'FFEEF2FF',
        altRow: 'FFF7FAFF',
        statusApproved: 'FF22C55E',
        statusPending: 'FFEAB308',
        statusRejected: 'FFEF4444',
        statusCancelled: 'FF9CA3AF',
        statusUpcoming: 'FF3B82F6'
      } as const;

      // Helpers
      const autoFitColumns = (ws: ExcelJS.Worksheet) => {
        ws.columns.forEach(col => {
          let max = 10;
          col.eachCell({ includeEmpty: true }, cell => {
            const v = cell.value as any;
            const len = v ? String(typeof v === 'object' ? (v.text || v.richText || v.hyperlink || '') : v).length : 0;
            if (len > max) max = len;
          });
          col.width = Math.min(60, Math.max(12, max + 2));
        });
      };

      const styleHeaderRow = (row: ExcelJS.Row) => {
        row.font = { bold: true, color: { argb: 'FF111827' } };
        row.alignment = { vertical: 'middle', horizontal: 'center' };
        row.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLORS.headerBg } } as any;
        row.height = 22;
      };

      const stripeRows = (ws: ExcelJS.Worksheet, start: number) => {
        for (let i = start; i <= ws.rowCount; i++) {
          if (i % 2 === 0) {
            ws.getRow(i).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: COLORS.altRow } } as any;
          }
          ws.getRow(i).height = 18;
        }
      };

      const wb = new ExcelJS.Workbook();
      wb.creator = 'KARE Hall';
      wb.created = new Date();

      // Sheet 1: Overview
      const s1 = wb.addWorksheet('Overview');
      s1.columns = [
        { header: 'Metric', key: 'metric', width: 30 },
        { header: 'Value', key: 'value', width: 20 }
      ];
      styleHeaderRow(s1.getRow(1));

      const s1Rows = [
        ['Total Bookings', bookings.length],
        ['Upcoming Bookings', upcoming.length],
        ['Approved Bookings', approved.length],
        ['Pending Bookings', pending.length],
        ['Rejected Bookings', rejected.length],
        ['Cancelled Bookings', cancelled.length]
      ];
      s1Rows.forEach(r => s1.addRow({ metric: r[0], value: r[1] }));
      stripeRows(s1, 2);
      autoFitColumns(s1);

      // Upcoming bookings table (next 10 days)
      s1.addRow([]);
      const titleRow = s1.addRow(['Upcoming Bookings (Next 10 days)']);
      titleRow.font = { bold: true, size: 12 };
      s1.mergeCells(`A${titleRow.number}:B${titleRow.number}`);
      const headersRow = s1.addRow(['Date','Booking ID','User Name','Department','Hall','From','To','Status']);
      styleHeaderRow(headersRow);
      const upcomingRows: Array<(string | number)>[] = [];
      bookings.forEach(b => {
        (b.dates || []).forEach(d => {
          if (isWithinNextTenDays(d)) {
            upcomingRows.push([
              d,
              b.bookingId || b.id || '',
              b.userName,
              b.department || b.userDepartment || '',
              b.hallName,
              b.timeFrom,
              b.timeTo,
              b.status
            ]);
          }
        });
      });
      // Sort by date ascending
      upcomingRows.sort((a, b) => String(a[0]).localeCompare(String(b[0])));
      upcomingRows.forEach(r => s1.addRow(r));
      stripeRows(s1, headersRow.number + 1);

      // Sheet 2: Bookings
      const s2 = wb.addWorksheet('Bookings');
      const bookingHeaders = [
        'Booking ID','User ID','User Name','Department','Hall','Dates','Time From','Time To','Status','Created At','Updated At','Purpose','Email','Approved By','Rejected By'
      ];
      s2.addRow(bookingHeaders);
      styleHeaderRow(s2.getRow(1));
      bookings.forEach(b => {
        s2.addRow([
          b.bookingId || b.id,
          b.userId,
          b.userName,
          b.department || b.userDepartment,
          b.hallName,
          (b.dates || []).join(', '),
          b.timeFrom,
          b.timeTo,
          b.status,
          typeof b.createdAt === 'string' ? b.createdAt : (b.createdAt?.toDate?.().toISOString?.() || ''),
          typeof b.updatedAt === 'string' ? b.updatedAt : (b.updatedAt?.toDate?.().toISOString?.() || ''),
          b.purpose,
          b.userEmail || '',
          b.approvedBy || '',
          b.rejectedBy || ''
        ]);
      });
      stripeRows(s2, 2);
      autoFitColumns(s2);

      // Sheet 3: Users
      const s3 = wb.addWorksheet('Users');
      const userHeaders = ['User ID','Name','Email','Department','Phone','Role','Designation','Cabin','Office Location','Created At'];
      s3.addRow(userHeaders);
      styleHeaderRow(s3.getRow(1));
      (users as User[]).forEach(u => {
        s3.addRow([
          (u as any).id || u.uid,
          u.name,
          u.email,
          u.department || '',
          (u as any).mobile || '',
          u.role,
          u.designation || '',
          u.cabinNumber || '',
          u.officeLocation || '',
          (u as any).createdAt || ''
        ]);
      });
      stripeRows(s3, 2);
      autoFitColumns(s3);

      // Sheet 4: Monthly Tracking
      const s4 = wb.addWorksheet('Monthly Tracking');
      s4.addRow(['Month', 'Bookings']);
      styleHeaderRow(s4.getRow(1));
      months.forEach((m, idx) => s4.addRow([m, monthlyCounts[idx]]));
      stripeRows(s4, 2);
      autoFitColumns(s4);

      // Sheet 5: Approved Events
      const s5 = wb.addWorksheet('Approved Events');
      const approvedHeaders = [
        'Booking ID','User ID','User Name','Email','Department','Hall','Purpose','Event Dates','From','To','Created At','Updated At','Approved By'
      ];
      s5.addRow(approvedHeaders);
      styleHeaderRow(s5.getRow(1));
      approved.forEach(b => {
        s5.addRow([
          b.bookingId || b.id,
          b.userId,
          b.userName,
          b.userEmail,
          b.department || b.userDepartment,
          b.hallName,
          b.purpose,
          (b.dates || []).join(', '),
          b.timeFrom,
          b.timeTo,
          typeof b.createdAt === 'string' ? b.createdAt : (b.createdAt?.toDate?.().toISOString?.() || ''),
          typeof b.updatedAt === 'string' ? b.updatedAt : (b.updatedAt?.toDate?.().toISOString?.() || ''),
          b.approvedBy || ''
        ]);
      });
      stripeRows(s5, 2);
      autoFitColumns(s5);

      // Download
      const buf = await wb.xlsx.writeBuffer();
      saveAs(new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
        `hall-booking-report-${new Date().toISOString().split('T')[0]}.xlsx`
      );
    } catch (e) {
      console.error('Error exporting Excel report:', e);
      alert('Failed to export report. Please try again.');
    }
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
