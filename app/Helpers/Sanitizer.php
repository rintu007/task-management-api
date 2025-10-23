<?php

namespace App\Helpers;

class Sanitizer
{
    /**
     * Sanitize input to remove all XSS threats
     */
    public static function sanitize($input): string
    {
        if (!is_string($input)) {
            return '';
        }

        // Remove NULL bytes
        $input = str_replace("\0", '', $input);
        
        // Remove all HTML tags and their content
        $input = self::removeScriptContent($input);
        
        // Remove any remaining HTML tags
        $input = strip_tags($input);
        
        // Remove JavaScript events and protocols
        $patterns = [
            '/on\w+\s*=\s*"[^"]*"/i',
            '/on\w+\s*=\s*\'[^\']*\'/i',
            '/on\w+\s*=\s*[^\s>]+/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/expression:/i',
            '/data:/i',
            '/fromCharCode:/i',
        ];

        $input = preg_replace($patterns, '', $input);

        return trim($input);
    }

    /**
     * Remove script tag content including the content between tags
     */
    private static function removeScriptContent($input): string
    {
        // Remove complete script tags with their content
        $input = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $input);
        
        // Remove any other dangerous tags completely (but keep the content between them)
        $dangerousTags = [
            'applet', 'embed', 'object', 'iframe', 'frame', 'frameset', 
            'ilayer', 'layer', 'bgsound', 'base', 'form', 'input', 'button',
            'select', 'textarea', 'meta', 'link', 'style'
        ];
        
        foreach ($dangerousTags as $tag) {
            $input = preg_replace("/<$tag\b[^>]*>.*?<\/$tag>/is", '', $input);
            $input = preg_replace("/<$tag\b[^>]*>/is", '', $input);
        }

        return $input;
    }
}