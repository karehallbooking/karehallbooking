<?php

namespace App\Helpers;

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QRHelper
{
    public static function generate(int $registrationId, int $eventId, string $userEmail): string
    {
        $secret = env('QR_SECRET', 'default-secret-key');
        
        // Remove base64: prefix if present (Laravel encrypted env format)
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }
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

    public static function renderSvg(string $qrString, int $size = 200): string
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
            return false;
        }

        [$payloadBase64, $signature] = $parts;
        $secret = env('QR_SECRET', 'default-secret-key');
        
        // Remove base64: prefix if present (Laravel encrypted env format)
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $expectedSignature = hash_hmac('sha256', $payloadBase64, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        $payloadJson = base64_decode($payloadBase64);
        $payload = json_decode($payloadJson, true);

        if (!$payload || !isset($payload['reg_id'], $payload['event_id'], $payload['user_email'], $payload['ts'])) {
            return false;
        }

        return $payload;
    }
}

