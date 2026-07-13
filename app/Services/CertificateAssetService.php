<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Throwable;

class CertificateAssetService
{
    public function url(?string $path): ?string
    {
        return $this->exists($path) ? Storage::disk('public')->url($path) : null;
    }

    public function dataUri(?string $path): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        try {
            $contents = Storage::disk('public')->get($path);
            $mime = Storage::disk('public')->mimeType($path);
        } catch (Throwable) {
            return null;
        }

        if (! is_string($contents) || $contents === '' || ! in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function exists(?string $path): bool
    {
        if (blank($path)) {
            return false;
        }

        try {
            return Storage::disk('public')->exists($path);
        } catch (Throwable) {
            return false;
        }
    }
}
