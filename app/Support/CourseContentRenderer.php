<?php

namespace App\Support;

use League\CommonMark\GithubFlavoredMarkdownConverter;

class CourseContentRenderer
{
    public static function render(?string $content): string
    {
        if (! filled($content)) {
            return '';
        }

        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = (string) $converter->convert($content);
        $html = self::stripUnsafeHtml($html);

        return self::wrapTables($html);
    }

    protected static function stripUnsafeHtml(string $html): string
    {
        $unsafeTags = 'script|iframe|object|embed|style|form|input|button|textarea|select|link|meta';

        $html = preg_replace(
            '#<\s*('.$unsafeTags.')[^>]*>.*?</\s*\1\s*>#is',
            '',
            $html
        ) ?? '';

        $html = preg_replace('#<\s*/?\s*('.$unsafeTags.')\b[^>]*>#i', '', $html) ?? '';
        $html = preg_replace('/\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html) ?? '';

        return self::neutralizeUnsafeUrls($html);
    }

    protected static function neutralizeUnsafeUrls(string $html): string
    {
        return preg_replace_callback(
            '/\s+(href|src)\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            function (array $matches): string {
                $attribute = strtolower($matches[1]);
                $value = html_entity_decode($matches[3] ?? $matches[4] ?? $matches[5] ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if (preg_match('/^\s*(javascript|vbscript|data):/i', $value)) {
                    return ' '.$attribute.'="#"';
                }

                return $matches[0];
            },
            $html
        ) ?? '';
    }

    protected static function wrapTables(string $html): string
    {
        return preg_replace('/<table>(.*?)<\/table>/is', '<div class="mk-rich-table"><table>$1</table></div>', $html) ?? $html;
    }
}