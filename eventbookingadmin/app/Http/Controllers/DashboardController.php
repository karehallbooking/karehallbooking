<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Registration;
use App\Models\AttendanceLog;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEvents = Event::count();
        $upcomingEvents = Event::where('status', 'upcoming')
            ->where('start_date', '>=', now()->toDateString())
            ->count();
        $totalRegistrations = Registration::where('payment_status', 'paid')->count();
        $todayAttendance = AttendanceLog::whereDate('scanned_at', today())->count();
        $recentRegistrations = Registration::with('event')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalEvents',
            'upcomingEvents',
            'totalRegistrations',
            'todayAttendance',
            'recentRegistrations'
        ));
    }
}

