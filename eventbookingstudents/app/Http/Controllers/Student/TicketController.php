<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Helpers\StudentTokenHelper;
use App\Models\Registration;
use App\Helpers\QRHelper;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function show($id, Request $request)
    {
        $registration = Registration::with(['event', 'ticket'])->findOrFail($id);
        
        // Verify event exists
        if (!$registration->event) {
            \Log::error('Event not found for registration', [
                'registration_id' => $registration->id,
                'event_id' => $registration->event_id,
            ]);
            abort(404, 'Event not found for this registration.');
        }
        
        // Verify student owns this ticket - check by token if available, otherwise by email
        $studentToken = StudentTokenHelper::getToken($request);
        $studentEmail = $request->session()->get('student_email');
        
        $isAuthorized = false;
        if ($studentToken && $registration->student_token === $studentToken) {
            $isAuthorized = true;
        } elseif ($studentEmail && $registration->student_email === $studentEmail) {
            $isAuthorized = true;
        } elseif (!$studentToken && !$studentEmail) {
            // No identifier available - allow access for backward compatibility during transition
            $isAuthorized = true;
        }
        
        if (!$isAuthorized) {
            abort(403, 'Unauthorized access to this ticket.');
        }

        // For paid events, require payment to be completed
        if ($registration->event->is_paid && $registration->payment_status !== 'paid') {
            abort(403, 'Ticket is only available after payment is completed.');
        }

        // For paid events, we need a ticket record
        // For free events, ticket record is optional (QR code is sufficient)
        if ($registration->event->is_paid) {
            if (!$registration->ticket) {
                \Log::warning('Ticket record not found for paid event registration', [
                    'registration_id' => $registration->id,
                    'event_id' => $registration->event_id,
                    'payment_status' => $registration->payment_status,
                ]);
                abort(404, 'Ticket not found. Please contact support if payment was completed.');
            }
        }

        // QR code is required for all events (free or paid)
        if (!$registration->qr_code) {
            \Log::error('QR code not found for registration', [
                'registration_id' => $registration->id,
                'event_id' => $registration->event_id,
                'is_paid' => $registration->event->is_paid,
                'payment_status' => $registration->payment_status,
            ]);
            abort(404, 'QR code not found for this registration. Please contact support.');
        }

        // Check if ticket PDF already exists
        $ticketPath = 'registrations/ticket_' . $registration->id . '.pdf';
        $fullPath = storage_path('app/' . $ticketPath);

        if (!file_exists($fullPath)) {
            // Generate ticket PDF on-the-fly
            $this->generateTicket($registration);
        }

        if (!file_exists($fullPath)) {
            abort(500, 'Failed to generate ticket.');
        }

        // Return PDF with inline headers
        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket_' . $registration->id . '.pdf"',
        ]);
    }

    protected function generateTicket(Registration $registration)
    {
        try {
            // Check if QR code exists
            if (empty($registration->qr_code)) {
                \Log::error('No QR code found for registration', [
                    'registration_id' => $registration->id,
                ]);
                throw new \Exception('QR code not found for this registration. Please contact support.');
            }
            
            // Generate QR SVG
            $qrSvg = QRHelper::renderSvg($registration->qr_code, 200);
            
            // Check if SVG was generated
            if (empty($qrSvg) || strlen($qrSvg) < 100) {
                \Log::error('QR SVG generation failed or returned empty', [
                    'registration_id' => $registration->id,
                    'qr_code_length' => strlen($registration->qr_code ?? ''),
                    'qr_code_preview' => substr($registration->qr_code ?? '', 0, 50) . '...',
                ]);
                throw new \Exception('Failed to generate QR code SVG. QR code may be invalid.');
            }

            // Convert SVG to base64 data URI for better PDF compatibility
            $qrSvgBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

            // Ensure directory exists
            $dir = storage_path('app/registrations');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Generate PDF using DomPDF
            $pdf = DomPDF::loadView('student.ticket.pdf', [
                'registration' => $registration,
                'event' => $registration->event,
                'qrSvg' => $qrSvg,
                'qrSvgBase64' => $qrSvgBase64,
            ])->setPaper([0, 0, 300, 420], 'portrait'); // Small ticket size (approx A6)
            
            // Set options for better SVG support
            $pdf->setOption('enable-html5-parser', true);
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);

            // Save PDF
            $ticketPath = 'registrations/ticket_' . $registration->id . '.pdf';
            $fullPath = storage_path('app/' . $ticketPath);
            $pdf->save($fullPath);
            
            \Log::info('Ticket PDF generated successfully', [
                'registration_id' => $registration->id,
                'file_path' => $fullPath,
                'file_exists' => file_exists($fullPath),
                'file_size' => file_exists($fullPath) ? filesize($fullPath) : 0,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ticket generation failed', [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}

