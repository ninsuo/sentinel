<?php

namespace App\Patch;

use App\Patch\Dto\FilePatch;

final class UnifiedDiffParser
{
    /**
     * @return list<FilePatch>
     */
    public function parse(string $patchText) : array
    {
        $patchText = str_replace(["\r\n", "\r"], "\n", trim($patchText));
        if ($patchText === '') {
            return [];
        }

        // Split on each file header: lines starting with "--- "
        // Keep the delimiter by using a regex with PREG_SPLIT_DELIM_CAPTURE.
        $parts = preg_split('/(^--- .*$)/m', $patchText, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (!is_array($parts) || count($parts) < 2) {
            return [];
        }

        $files = [];
        // parts structure: [before, '--- ...', rest, '--- ...', rest, ...]
        for ($i = 1; $i < count($parts); $i += 2) {
            $lineOld = trim($parts[$i]);
            $rest = $parts[$i + 1] ?? '';
            $chunk = $lineOld."\n".ltrim($rest, "\n");

            // Need the +++ line
            if (!preg_match('/^\+\+\+ (.+)$/m', $chunk, $mNew)) {
                continue;
            }

            $oldPath = $this->extractPathFromHeader($lineOld);
            $newPath = $this->extractPathFromHeader('+++ '.$mNew[1]);

            $files[] = new FilePatch(
                oldPath: $oldPath,
                newPath: $newPath,
                diff: rtrim($chunk, "\n")."\n",
                isNewFile: ($oldPath === '/dev/null' && $newPath !== '/dev/null'),
                isDeletedFile: ($newPath === '/dev/null' && $oldPath !== '/dev/null'),
            );
        }

        return $files;
    }

    private function extractPathFromHeader(string $line) : string
    {
        // Line is like: "--- /dev/null" or "+++ src/Foo.php"
        $line = trim($line);
        $parts = preg_split('/\s+/', $line, 2);
        $path = $parts[1] ?? '';
        $path = trim($path);

        // Some diffs include timestamps after filename: "+++ a/file\t2026-..."
        $path = preg_split('/\t/', $path, 2)[0] ?? $path;

        return $path;
    }
}
