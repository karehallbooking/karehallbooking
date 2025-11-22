<?php

namespace App\Helpers;

use App\Models\Setting;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QRHelper
{
    public static function generate(int $registrationId, int $eventId, string $userEmail): string
    {
        $secret = Setting::get('qr_secret_key', env('QR_SECRET', 'default-secret-key'));
        $timestamp = time();

        $payload = [
            'reg_id' => $registrationId,
            'event_id' => $eventId,
            'user_email' => $userEmail,
            'ts' => $timestamp,
        ];

        $payloadJson = json_encode($payload);
        $payloadBase64 = base64_encode($payloadJson);
        $signature = hash_hmac('sha256', $payloadBase64, $secret);

        return $payloadBase64 . '.' . $signature;
    }

    public static function renderSvg(string $qrString, int $size = 300): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrString);
    }

    public static function verify(string $qrCode): array|bool
    {
        $parts = explode('.', $qrCode);
        if (count($parts) !== 2) {
            \Log::warning('QR Verify: Invalid format - not 2 parts', ['parts_count' => count($parts)]);
            return false;
        }

        [$payloadBase64, $signature] = $parts;
        
        // IMPORTANT: Student app uses env('QR_SECRET', 'default-secret-key'), so we MUST match exactly
        // Student app generates QR codes with: env('QR_SECRET', 'default-secret-key')
        // So admin must verify with the EXACT same value
        $secretFromEnv = env('QR_SECRET', 'default-secret-key'); // Use same default as student app
        
        // Remove base64: prefix if present (Laravel encrypted env format)
        if (str_starts_with($secretFromEnv, 'base64:')) {
            $secretFromEnv = base64_decode(substr($secretFromEnv, 7));
        }
        $secretFromSetting = Setting::get('qr_secret_key', null);
        
        // ALWAYS prioritize env() with same default as student app
        $secretsToTry = [];
        // Always try env() first (with default) since student app uses this
        $secretsToTry[] = ['source' => 'env', 'secret' => $secretFromEnv];
        
        // Only try setting if it's different from env (for backward compatibility)
        if (!empty($secretFromSetting) && $secretFromSetting !== $secretFromEnv) {
            $secretsToTry[] = ['source' => 'setting', 'secret' => $secretFromSetting];
        }
        
        // Also try default secret for backward compatibility with old QR codes
        // (QR codes generated before QR_SECRET was set in .env)
        if ($secretFromEnv !== 'default-secret-key') {
            $secretsToTry[] = ['source' => 'default-legacy', 'secret' => 'default-secret-key'];
        }

        \Log::info('QR Verify: Trying secrets', [
            'secrets_count' => count($secretsToTry),
            'sources' => array_column($secretsToTry, 'source'),
        ]);

        $verified = false;
        $usedSecret = null;
        
        // Try each secret until one matches
        foreach ($secretsToTry as $secretInfo) {
            $testSecret = $secretInfo['secret'];
            $expectedSignature = hash_hmac('sha256', $payloadBase64, $testSecret);
            
            if (hash_equals($expectedSignature, $signature)) {
                $verified = true;
                $usedSecret = $secretInfo['source'];
                \Log::info('QR Verify: Signature matched', [
                    'secret_source' => $secretInfo['source'],
                ]);
                break;
            }
        }
        
        if (!$verified) {
            \Log::warning('QR Verify: Signature mismatch with all secrets', [
                'tried_sources' => array_column($secretsToTry, 'source'),
                'received_signature_preview' => substr($signature, 0, 20) . '...',
            ]);
            return false;
        }

        $payloadJson = base64_decode($payloadBase64);
        $payload = json_decode($payloadJson, true);

        // Validate payload structure - accept both user_email and student_email
        if (!$payload || !isset($payload['reg_id'], $payload['event_id'], $payload['ts'])) {
            \Log::warning('QR Verify: Invalid payload structure', [
                'has_payload' => !empty($payload),
                'has_reg_id' => isset($payload['reg_id']),
                'has_event_id' => isset($payload['event_id']),
                'has_user_email' => isset($payload['user_email']),
                'has_student_email' => isset($payload['student_email']),
                'has_ts' => isset($payload['ts']),
            ]);
            return false;
        }
        
        // Normalize email field - student app uses 'student_email', admin expects 'user_email'
        if (isset($payload['student_email']) && !isset($payload['user_email'])) {
            $payload['user_email'] = $payload['student_email'];
            \Log::info('QR Verify: Normalized student_email to user_email', [
                'reg_id' => $payload['reg_id'] ?? null,
            ]);
        }

        // Check if QR is not too old (24 hours) - but make it more lenient for testing
        $maxAge = 24 * 60 * 60;
        $age = time() - $payload['ts'];
        if ($age > $maxAge) {
            \Log::warning('QR Verify: QR expired', [
                'age_hours' => round($age / 3600, 2),
                'max_age_hours' => $maxAge / 3600,
            ]);
            return false;
        }

        \Log::info('QR Verify: Success', [
            'reg_id' => $payload['reg_id'],
            'event_id' => $payload['event_id'],
            'age_hours' => round($age / 3600, 2),
        ]);

        return $payload;
    }
}

