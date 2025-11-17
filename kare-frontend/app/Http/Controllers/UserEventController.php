<?php
namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewBookingNotification;

class UserEventController extends Controller
{
    public function dashboard()
    {
        $today = now()->toDateString();
        $total = DB::table('events')->count();
        $pending = DB::table('events')->where('status', 'pending')->count();
        $approved = DB::table('events')->where('status', 'approved')->count();
        $upcoming = DB::table('events')->whereDate('event_date', '>=', $today)->count();

        $stats = compact('total', 'pending', 'approved', 'upcoming');

        return view('kare.dashboard', compact('stats'));
    }

    public function halls()
    {
        $halls = DB::table('halls')->orderBy('name')->get();
        
        // Load facilities for each hall from database (using facility_name directly)
        foreach ($halls as $hall) {
            try {
                // Load facilities directly from hall_facilities table using facility_name
                $hall->facilities_list = DB::table('hall_facilities')
                    ->where('hall_id', $hall->id)
                    ->pluck('facility_name')
                    ->toArray();
            } catch (\Exception $e) {
                // Fallback: try to decode from JSON if using old structure
                try {
                    $hallFacilities = json_decode($hall->facilities ?? '[]', true);
                    $hall->facilities_list = is_array($hallFacilities) ? $hallFacilities : [];
                } catch (\Exception $e2) {
                    $hall->facilities_list = [];
                }
            }
        }
        
        return view('kare.halls', compact('halls'));
    }

    public function create($hallId = null)
    {
        $selectedHall = null;
        $availableFacilities = [];
        
        if ($hallId !== null) {
            $selectedHall = DB::table('halls')->where('id', $hallId)->first();
            if ($selectedHall) {
                // Load facilities from database for selected hall (using facility_name directly)
                try {
                    $availableFacilities = DB::table('hall_facilities')
                        ->where('hall_id', $selectedHall->id)
                        ->pluck('facility_name')
                        ->toArray();
                } catch (\Exception $e) {
                    // Fallback: try to decode from JSON if using old structure
                    try {
                        if (!empty($selectedHall->facilities)) {
                            $facs = json_decode($selectedHall->facilities, true);
                            if (is_array($facs) && !empty($facs)) {
                                $availableFacilities = $facs;
                            }
                        }
                    } catch (\Exception $e2) {
                        $availableFacilities = [];
                    }
                }
            }
        }
        
        // If no hall selected or no facilities found, use empty array (no dummy data)
        if (empty($availableFacilities)) {
            $availableFacilities = [];
        }
        
        $halls = DB::table('halls')->orderBy('name')->pluck('name', 'id');
        return view('kare.book', compact('halls', 'selectedHall', 'availableFacilities'));
    }

    public function store(Request $request)
    {
        // If hall_id provided, always map to canonical hall_name from DB BEFORE validation
        if ($request->filled('hall_id')) {
            $hall = DB::table('halls')->where('id', (int) $request->hall_id)->first();
            if ($hall) {
                $request->merge(['hall_name' => $hall->name]);
            }
        }

        $rules = Event::getCreateRules();
        $rules['event_date_checkout'] = 'required|date|after_or_equal:event_date';
        // tighten seating capacity to hall capacity if known
        $hallForLimit = null;
        if ($request->filled('hall_id')) {
            $hallForLimit = DB::table('halls')->where('id', (int) $request->hall_id)->first();
        } elseif ($request->filled('hall_name')) {
            $hallForLimit = DB::table('halls')->where('name', $request->hall_name)->first();
        }
        if ($hallForLimit && isset($hallForLimit->capacity)) {
            $rules['seating_capacity'] = 'required|integer|min:1|max:' . (int) $hallForLimit->capacity;
        }

        $messages = [];
        if ($hallForLimit && isset($hallForLimit->capacity)) {
            $messages['seating_capacity.max'] = 'Please select capacity within ' . (int) $hallForLimit->capacity;
        }
        $messages['event_date_checkout.after_or_equal'] = 'Check-out date must be after or same as check-in date.';
        // Add PDF validation
        $rules['event_brochure'] = 'nullable|file|mimes:pdf|max:5120';
        $rules['approval_letter'] = 'nullable|file|mimes:pdf|max:5120';
        $messages['event_brochure.mimes'] = 'Event brochure must be a PDF file.';
        $messages['approval_letter.mimes'] = 'Approval letter must be a PDF file.';

        // after validation, fetch facilities of the selected hall to repopulate in case of error
        $availableFacilities = [];
        $hallId = $request->input('hall_id');
        if ($hallId) {
            try {
                // Load facilities from database for selected hall
                $availableFacilities = DB::table('hall_facilities')
                    ->where('hall_id', $hallId)
                    ->pluck('facility_name')
                    ->toArray();
            } catch (\Exception $e) {
                // Fallback: try to decode from JSON if using old structure
                $hallRow = DB::table('halls')->where('id', $hallId)->first();
                if ($hallRow && !empty($hallRow->facilities)) {
                    $facs = json_decode($hallRow->facilities, true);
                    if (is_array($facs) && !empty($facs)) {
                        $availableFacilities = $facs;
                    }
                }
            }
        }
        // validation block (do not move)
        $request->validate($rules, $messages);
        // custom single-day check remains (as previously added)
        if ($request->event_date == $request->event_date_checkout) {
            if (strtotime($request->time_from) >= strtotime($request->time_to)) {
                return back()->withErrors(['time_to' => 'For single-day bookings, Check-out time must be after Check-in time.'])->withInput()->with(compact('availableFacilities'));
            }
        }

        $conflicts = Event::checkConflicts(
            $request->hall_name,
            $request->event_date,
            $request->time_from,
            $request->time_to
        );

        if ($conflicts->count() > 0) {
            return back()->withErrors(['conflict' => 'Selected time conflicts with existing bookings.'])->withInput();
        }

        // --- PDF File Upload Logic ---
        $brochurePath = null;
        if ($request->hasFile('event_brochure')) {
            $brochurePath = $request->file('event_brochure')->store('brochures', 'public');
        }
        $letterPath = null;
        if ($request->hasFile('approval_letter')) {
            $letterPath = $request->file('approval_letter')->store('letters', 'public');
        }

        $event = Event::create([
            'hall_name' => $request->hall_name,
            'event_date' => $request->event_date,
            'event_date_checkout' => $request->event_date_checkout,
            'time_from' => $request->time_from,
            'time_to' => $request->time_to,
            'organizer_name' => $request->organizer_name,
            'organizer_email' => $request->organizer_email,
            'organizer_phone' => $request->organizer_phone,
            'organizer_department' => $request->organizer_department,
            'organizer_designation' => $request->organizer_designation,
            'purpose' => $request->purpose,
            'seating_capacity' => $request->seating_capacity,
            'facilities_required' => $request->facilities_required ?? [],
            'status' => Event::STATUS_PENDING,
            'created_by' => 'User',
            'event_brochure_path' => $brochurePath,
            'approval_letter_path' => $letterPath,
        ]);

        // Send email notification to admin
        try {
            $adminEmail = $this->getAdminEmail();
            \Log::info('Attempting to send booking notification email', [
                'admin_email' => $adminEmail,
                'event_id' => $event->id,
                'mail_mailer' => config('mail.default'),
                'mail_from' => config('mail.from.address'),
            ]);
            
            if ($adminEmail) {
                Mail::to($adminEmail)->send(new NewBookingNotification($event));
                \Log::info('Booking notification email sent successfully', [
                    'admin_email' => $adminEmail,
                    'event_id' => $event->id,
                ]);
            } else {
                \Log::warning('Admin email not found in database. Email notification not sent.', [
                    'event_id' => $event->id,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the booking creation
            \Log::error('Failed to send booking notification email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'event_id' => $event->id,
            ]);
        }

        return redirect()->route('kare.myBookings')->with('success', 'Booking submitted. Pending approval.');
    }

    public function myBookings(Request $request)
    {
        $query = DB::table('events')->orderByDesc('created_at');

        if ($request->filled('status')) {
            $status = $request->status;
            $query->where('status', $status);
            // If viewing approved list, hide bookings that have a pending or approved cancel request
            if ($status === 'approved') {
                $query->whereNotExists(function ($sub) {
                    $sub->from('cancel_requests')
                        ->whereColumn('cancel_requests.event_id', 'events.id')
                        ->whereIn('cancel_requests.status', ['pending','approved']);
                });
            }
        }

        if ($request->get('scope') === 'upcoming') {
            $query->whereDate('event_date', '>=', now()->toDateString());
        }

        $events = $query->paginate(10);
        return view('kare.my-bookings', compact('events'));
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'hall_name' => 'required|string',
            'event_date' => 'required|date',
            'time_from' => 'required|date_format:H:i',
            'time_to' => 'required|date_format:H:i|after:time_from',
        ]);

        $conflicts = Event::checkConflicts(
            $request->hall_name,
            $request->event_date,
            $request->time_from,
            $request->time_to
        );

        return response()->json([
            'available' => $conflicts->count() === 0,
        ]);
    }

    public function cancelRequests()
    {
        $requests = \DB::table('cancel_requests')
            ->join('events', 'cancel_requests.event_id', '=', 'events.id')
            ->select('cancel_requests.*', 'events.hall_name', 'events.event_date')
            ->orderByDesc('cancel_requests.created_at')
            ->get();
        return view('kare.cancel-requests', compact('requests'));
    }

    /**
     * Get admin email from database
     */
    private function getAdminEmail(): ?string {
        try {
            $admin = DB::table('admin_settings')
                ->where('is_active', 1)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($admin) {
                \Log::info('Admin email retrieved from database', [
                    'admin_email' => $admin->admin_email,
                ]);
                return $admin->admin_email;
            } else {
                \Log::warning('No active admin email found in admin_settings table');
                return null;
            }
        } catch (\Exception $e) {
            \Log::error('Error retrieving admin email from database', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

