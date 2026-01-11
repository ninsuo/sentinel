<?php

namespace App\Patch;

final class PatchExtractor
{
    public function extractUnifiedDiff(string $raw) : string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return '';
        }

        // 1) Prefer fenced ```diff blocks
        if (preg_match('/```(?:diff)?\s*(.*?)```/is', $raw, $m)) {
            $candidate = trim($m[1]);
            if ($this->looksLikeUnifiedDiff($candidate)) {
                return $this->normalize($candidate);
            }
        }

        // 2) If raw contains a diff without fences, extract from first ---/+++ marker onward
        $pos = strpos($raw, "\n--- ");
        if ($pos === false) {
            $pos = strpos($raw, "--- ");
            if ($pos === false) {
                return '';
            }
        }

        $candidate = trim(substr($raw, $pos));
        if ($this->looksLikeUnifiedDiff($candidate)) {
            return $this->normalize($candidate);
        }

        return '';
    }

    private function looksLikeUnifiedDiff(string $text) : bool
    {
        return (str_contains($text, "\n--- ") || str_starts_with($text, "--- "))
               && (str_contains($text, "\n+++ ") || str_contains($text, "+++ "));
    }

    private function normalize(string $diff) : string
    {
        // Normalize line endings + trim trailing spaces
        $diff = str_replace(["\r\n", "\r"], "\n", $diff);

        // Remove leading BOM if any
        $diff = preg_replace('/^\xEF\xBB\xBF/', '', $diff) ?? $diff;

        // Ensure it ends with newline
        if ($diff !== '' && !str_ends_with($diff, "\n")) {
            $diff .= "\n";
        }

        return $diff;
    }
}
