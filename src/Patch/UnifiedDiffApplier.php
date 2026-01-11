<?php

namespace App\Patch;

final class UnifiedDiffApplier
{
    public function applyToString(string $original, string $fileDiff) : string
    {
        $original = str_replace(["\r\n", "\r"], "\n", $original);
        $lines = $original === '' ? [] : explode("\n", $original);

        // Keep the trailing newline behavior consistent:
        // explode() loses info, but we'll re-add newline at the end later.
        $hadTrailingNewline = $original === '' ? false : str_ends_with($original, "\n");

        $diffLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $fileDiff));

        $out = [];
        $srcIndex = 0;

        $i = 0;
        while ($i < count($diffLines)) {
            $line = $diffLines[$i];

            if (!str_starts_with($line, '@@')) {
                $i++;
                continue;
            }

            // Parse hunk header: @@ -l,s +l,s @@
            if (!preg_match('/^@@\s+\-(\d+)(?:,(\d+))?\s+\+(\d+)(?:,(\d+))?\s+@@/', $line, $m)) {
                throw new \RuntimeException('Invalid hunk header: '.$line);
            }

            $oldStart = (int) $m[1];

            // Copy untouched lines before this hunk.
            // oldStart is 1-based line number in original file.
            $targetIndex = max(0, $oldStart - 1);

            while ($srcIndex < $targetIndex && $srcIndex < count($lines)) {
                $out[] = $lines[$srcIndex];
                $srcIndex++;
            }

            $i++;

            // Apply hunk body
            while ($i < count($diffLines)) {
                $h = $diffLines[$i];

                // next hunk
                if (str_starts_with($h, '@@')) {
                    break;
                }

                if ($h === '\ No newline at end of file') {
                    $i++;
                    continue;
                }

                $prefix = $h !== '' ? $h[0] : ' ';
                $content = $prefix === '+' || $prefix === '-' || $prefix === ' ' ? substr($h, 1) : $h;

                if ($prefix === ' ') {
                    // context must match
                    $current = $lines[$srcIndex] ?? null;
                    if ($current !== $content) {
                        throw new \RuntimeException(sprintf(
                            "Hunk context mismatch. Expected '%s', got '%s' at line %d",
                            $content,
                            (string) $current,
                            $srcIndex + 1
                        ));
                    }
                    $out[] = $content;
                    $srcIndex++;
                } elseif ($prefix === '-') {
                    // removed line must match
                    $current = $lines[$srcIndex] ?? null;
                    if ($current !== $content) {
                        throw new \RuntimeException(sprintf(
                            "Hunk delete mismatch. Expected '%s', got '%s' at line %d",
                            $content,
                            (string) $current,
                            $srcIndex + 1
                        ));
                    }
                    $srcIndex++;
                } elseif ($prefix === '+') {
                    $out[] = $content;
                } else {
                    // ignore junk
                }

                $i++;
            }
        }

        // Copy remaining lines
        while ($srcIndex < count($lines)) {
            $out[] = $lines[$srcIndex];
            $srcIndex++;
        }

        $result = implode("\n", $out);

        // restore trailing newline if original had one OR if result isn't empty (most files should end with newline)
        if ($result !== '' && ($hadTrailingNewline || true)) {
            $result .= "\n";
        }

        return $result;
    }
}
