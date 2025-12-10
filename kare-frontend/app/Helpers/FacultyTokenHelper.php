<?php

namespace App\Helpers;

use Illuminate\Http\Request;

class FacultyTokenHelper
{
    /**
     * Resolve the faculty token from header, session or (optional) query parameter.
     */
    public static function getToken(Request $request): ?string
    {
        // 1. Header provided by the college portal
        $token = $request->header('X-FACULTY-TOKEN');
        if ($token) {
            $token = trim($token);
            $request->session()->put('faculty_token', $token);
            return $token;
        }

        // 2. Session (persists between requests)
        $token = $request->session()->get('faculty_token');
        if ($token) {
            return trim($token);
        }

        // 3. TEMPORARY: query parameter for manual/local testing if enabled
        if (config('app.allow_faculty_token_query_param', false)) {
            $token = $request->query('faculty_token') ?? $request->input('faculty_token');
            if ($token) {
                $token = trim($token);
                $request->session()->put('faculty_token', $token);
                return $token;
            }
        }

        return null;
    }

    /**
     * Require a token for routes that must be faculty-scoped.
     */
    public static function requireToken(Request $request): string
    {
        $token = self::getToken($request);

        if (!$token) {
            abort(401, 'Faculty token is required. Please access this page via the college portal.');
        }

        return $token;
    }
}

