<?php

namespace App\Http\Support;

class XssHelper
{
    /**
     * Sanitize HTML content to prevent XSS attacks.
     * Removes script tags and dangerous event handlers.
     */
    public static function sanitize(string $html): string
    {
        // Remove script tags
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i', '', $html);

        // Remove event handlers (onclick, onload, etc.)
        $html = preg_replace('/\s+(on\w+)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $html);

        // Remove javascript: URLs
        $html = preg_replace('/javascript:/i', '', $html);

        return $html;
    }
}
