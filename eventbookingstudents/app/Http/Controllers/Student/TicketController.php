<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Helpers\QRHelper;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function show($id, Request $request)
    {
        $registration = Registration::with('event')->findOrFail($id);
        
        // Optional: Verify student email matches (for security)
        $studentEmail = $request->session()->get('student_email');
        if ($studentEmail && $registration->student_email !== $studentEmail) {
            abort(403, 'Unauthorized access to this ticket.');
        }

        if (!$registration->qr_code) {
            abort(404, 'QR code not found for this registration.');
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

