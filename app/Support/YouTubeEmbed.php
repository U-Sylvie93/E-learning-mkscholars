<?php

namespace App\Support;

class YouTubeEmbed
{
    public static function embedUrl(?string $url): ?string
    {
        $videoId = self::videoId($url);

        return $videoId ? 'https://www.youtube.com/embed/'.$videoId : null;
    }

    public static function videoId(?string $url): ?string
    {
        if (! filled($url)) {
            return null;
        }

        $url = trim($url);

        if (preg_match('/^\s*(javascript|data|vbscript):/i', $url)) {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        $host = str_starts_with($host, 'www.') ? substr($host, 4) : $host;
        $path = trim($parts['path'] ?? '', '/');

        if ($host === 'youtu.be') {
            return self::sanitizeVideoId(str($path)->before('/')->toString());
        }

        if (! in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            return null;
        }

        if ($path === 'watch') {
            parse_str($parts['query'] ?? '', $query);

            return self::sanitizeVideoId($query['v'] ?? null);
        }

        foreach (['embed/', 'shorts/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return self::sanitizeVideoId(str($path)->after($prefix)->before('/')->before('?')->toString());
            }
        }

        return null;
    }

    protected static function sanitizeVideoId(?string $videoId): ?string
    {
        if (! is_string($videoId)) {
            return null;
        }

        $videoId = trim($videoId);

        return preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1 ? $videoId : null;
    }
}