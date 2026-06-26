<?php

namespace App\Rules;

use App\Support\YouTubeEmbed;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YouTubeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if (! is_string($value) || YouTubeEmbed::embedUrl($value) === null) {
            $fail('Please enter a valid YouTube watch, youtu.be, shorts, or embed URL.');
        }
    }
}