<?php

declare(strict_types=1);

namespace App\Support;

class PhoneHelper
{
    /**
     * Normalize a phone number to the canonical Bolivian format: 591XXXXXXXX.
     *
     * Rules:
     * - Remove spaces, dashes, parentheses, plus sign, and any other non-numeric character.
     * - If it begins with 591, leave it as is.
     * - If it has exactly 8 digits, prepend 591.
     * - Otherwise, return the numeric-only representation.
     */
    public static function normalize(string $phone): string
    {
        // Remove all non-numeric characters
        $normalized = preg_replace('/\D/', '', $phone);

        if ($normalized === null || $normalized === '') {
            return '';
        }

        // If it starts with 591, return it
        if (str_starts_with($normalized, '591')) {
            return $normalized;
        }

        // If it has exactly 8 digits, prepend 591
        if (strlen($normalized) === 8) {
            return '591' . $normalized;
        }

        return $normalized;
    }
}
