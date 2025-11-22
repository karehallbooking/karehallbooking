<?php

use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\QRScannerController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');

Route::prefix('events')->name('admin.events.')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/export/csv', [EventController::class, 'exportCsv'])->name('export.csv');
    Route::get('/create', [EventController::class, 'create'])->name('create');
    Route::post('/', [EventController::class, 'store'])->name('store');
    Route::get('/{id}/brochure/download', [EventController::class, 'downloadBrochure'])->name('brochure.download');
    Route::get('/{id}/attachment/download', [EventController::class, 'downloadAttachment'])->name('attachment.download');
    Route::get('/{id}/edit', [EventController::class, 'edit'])->name('edit');
    Route::put('/{id}', [EventController::class, 'update'])->name('update');
    Route::delete('/{id}', [EventController::class, 'destroy'])->name('destroy');
    Route::get('/{id}', [EventController::class, 'show'])->name('show');
});

Route::prefix('registrations')->name('admin.registrations.')->group(function () {
    Route::get('/', [RegistrationController::class, 'index'])->name('index');
    Route::get('/export/csv', [RegistrationController::class, 'exportCsv'])->name('export.csv');
    Route::get('/create', [RegistrationController::class, 'create'])->name('create');
    Route::post('/', [RegistrationController::class, 'store'])->name('store');
    Route::get('/event/{event}', [RegistrationController::class, 'byEvent'])->name('by-event');
    Route::get('/{registration}/edit', [RegistrationController::class, 'edit'])->name('edit');
    Route::put('/{registration}', [RegistrationController::class, 'update'])->name('update');
    Route::post('/{registration}/attendance', [RegistrationController::class, 'markAttendance'])->name('attendance');
    Route::post('/{registration}/payment-status', [RegistrationController::class, 'updatePaymentStatus'])->name('payment-status');
    Route::post('/{registration}/qr/regenerate', [RegistrationController::class, 'regenerateQr'])->name('qr.regenerate');
    Route::get('/{registration}/qr/download', [RegistrationController::class, 'downloadQr'])->name('qr.download');
});

Route::prefix('scanner')->name('admin.scanner.')->group(function () {
    Route::get('/', [QRScannerController::class, 'index'])->name('index');
    Route::post('/scan', [QRScannerController::class, 'scan'])->name('scan');
    Route::post('/confirm', [QRScannerController::class, 'confirmAttendance'])->name('confirm');
    Route::post('/mark-absent', [QRScannerController::class, 'markAbsent'])->name('mark-absent');
    Route::post('/revoke', [QRScannerController::class, 'revoke'])->name('revoke');
});

Route::prefix('attendance')->name('admin.attendance.')->group(function () {
    Route::get('/', [AttendanceLogController::class, 'index'])->name('index');
    Route::get('/export/csv', [AttendanceLogController::class, 'exportCsv'])->name('export.csv');
    Route::post('/bulk-absent', [AttendanceLogController::class, 'bulkMarkAbsent'])->name('bulk.absent');
});

Route::prefix('certificates')->name('admin.certificates.')->group(function () {
    Route::get('/', [CertificateController::class, 'index'])->name('index');
    Route::post('/events/{event}/upload-template', [CertificateController::class, 'uploadTemplate'])->name('upload-template');
    Route::post('/events/{event}/generate', [CertificateController::class, 'generateForEvent'])->name('generate.event');
    Route::get('/events/{event}/download-all', [CertificateController::class, 'downloadAll'])->name('download-all');
    Route::get('/{certificate}/download', [CertificateController::class, 'download'])->name('download');
    Route::post('/{certificate}/revoke', [CertificateController::class, 'revoke'])->name('revoke');
});

Route::prefix('payments')->name('admin.payments.')->group(function () {
    Route::get('/', [PaymentController::class, 'index'])->name('index');
    Route::post('/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('mark-paid');
    Route::post('/{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
    Route::get('/export/csv', [PaymentController::class, 'exportCsv'])->name('export.csv');
});

Route::prefix('reports')->name('admin.reports.')->group(function () {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/registrations', [ReportController::class, 'registrationsCsv'])->name('registrations.csv');
    Route::get('/attendance', [ReportController::class, 'attendanceCsv'])->name('attendance.csv');
    Route::get('/payments', [ReportController::class, 'paymentsCsv'])->name('payments.csv');
    Route::get('/export', [ReportController::class, 'exportFullReport'])->name('export.full');
    Route::get('/monthly', [ReportController::class, 'monthlyReport'])->name('monthly');
    Route::get('/event/{event}', [ReportController::class, 'eventReport'])->name('event');
    Route::get('/print-summary', [ReportController::class, 'printSummary'])->name('summary');
});

Route::prefix('settings')->name('admin.settings.')->group(function () {
    Route::get('/', [SettingController::class, 'index'])->name('index');
    Route::post('/qr-secret', [SettingController::class, 'updateQrSecret'])->name('qr-secret');
    Route::post('/event-rules', [SettingController::class, 'updateEventRules'])->name('event-rules');
    Route::post('/certificate-template', [SettingController::class, 'updateCertificateTemplate'])->name('certificate-template');
    Route::post('/storage-path', [SettingController::class, 'updateStoragePath'])->name('storage-path');
    Route::post('/system-info', [SettingController::class, 'updateSystemInfo'])->name('system-info');
});
