<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'qr_secret_key' => Setting::get('qr_secret_key', 'default-secret-key'),
            'event_rules' => Setting::get('event_rules', 'Default capacity 100'),
            'certificate_template' => Setting::get('certificate_template', 'This is to certify that {{name}} attended {{event}} on {{date}}.'),
            'certificate_storage_path' => Setting::get('certificate_storage_path', 'certificates'),
            'system_info' => Setting::get('system_info', 'Event Booking Admin'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    public function updateQrSecret(Request $request)
    {
        $request->validate([
            'qr_secret_key' => 'required|string|min:16',
        ]);

        Setting::set('qr_secret_key', $request->qr_secret_key);

        return back()->with('success', 'QR secret key updated.');
    }

    public function updateEventRules(Request $request)
    {
        $request->validate([
            'event_rules' => 'required|string',
        ]);

        Setting::set('event_rules', $request->event_rules);

        return back()->with('success', 'Event rules updated.');
    }

    public function updateCertificateTemplate(Request $request)
    {
        $request->validate([
            'certificate_template' => 'required|string',
        ]);

        Setting::set('certificate_template', $request->certificate_template);

        return back()->with('success', 'Certificate template updated.');
    }

    public function updateStoragePath(Request $request)
    {
        $request->validate([
            'certificate_storage_path' => 'required|string',
        ]);

        Setting::set('certificate_storage_path', $request->certificate_storage_path);

        return back()->with('success', 'Certificate storage path updated.');
    }

    public function updateSystemInfo(Request $request)
    {
        $request->validate([
            'system_info' => 'required|string',
        ]);

        Setting::set('system_info', $request->system_info);

        return back()->with('success', 'System info updated.');
    }
}


