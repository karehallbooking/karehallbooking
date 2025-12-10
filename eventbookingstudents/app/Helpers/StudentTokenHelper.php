<?php

namespace App\Helpers;

use Illuminate\Http\Request;

/**
 * StudentTokenHelper
 * 
 * Retrieves the current student's token from request headers or session.
 * This token is used to differentiate registrations per student when integrated
 * with the college portal authentication system.
 * 
 * Priority order:
 * 1. X-STUDENT-TOKEN header (for real portal integration)
 * 2. Session value (if portal sets it via session)
 * 3. Query parameter (TEMPORARY - for local testing only, must be enabled via config)
 */
class StudentTokenHelper
{
    /**
     * Get the current student token from request
     * 
     * @param Request $request
     * @return string|null The student token, or null if not found
     */
    public static function getToken(Request $request): ?string
    {
        // Priority 1: Check request header (X-STUDENT-TOKEN)
        // This is the primary method for portal integration
        $token = $request->header('X-STUDENT-TOKEN');
        if ($token) {
            return trim($token);
        }

        // Priority 2: Check session
        // Portal may set this via session when student logs in
        $token = $request->session()->get('student_token');
        if ($token) {
            return trim($token);
        }

        // Priority 3: TEMPORARY - Query parameter for local testing
        // Only allow if explicitly enabled in config
        // Remove this in production or keep disabled
        $allowQueryParam = config('app.allow_student_token_query_param', false);
        if ($allowQueryParam) {
            $token = $request->query('student_token');
            if ($token) {
                return trim($token);
            }
        }

        return null;
    }

    /**
     * Check if a token is available for the current request
     * 
     * @param Request $request
     * @return bool
     */
    public static function hasToken(Request $request): bool
    {
        return self::getToken($request) !== null;
    }

    /**
     * Require a token - throws exception if not found (for production)
     * Returns null if not found and in testing mode
     * 
     * @param Request $request
     * @param bool $throwException Whether to throw exception if token not found
     * @return string|null
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    public static function requireToken(Request $request, bool $throwException = false): ?string
    {
        $token = self::getToken($request);
        
        if (!$token && $throwException) {
            abort(401, 'Student token is required. Please log in through the college portal.');
        }

        return $token;
    }
}

