<?php

namespace App\Patch;

use App\Patch\Dto\FilePatch;

final class PatchRewriter
{
    /**
     * @param list<FilePatch>       $filePatches
     * @param array<string, string> $renameMap key => newPath (only for new files)
     */
    public function rewriteNewFileDestinations(string $patchText, array $filePatches, array $renameMap) : string
    {
        $patchText = str_replace(["\r\n", "\r"], "\n", $patchText);

        foreach ($filePatches as $fp) {
            if (!$fp->isNewFile) {
                continue;
            }

            $key = $fp->key();
            if (!isset($renameMap[$key])) {
                continue;
            }

            $wanted = $this->normalizeRelativePath($renameMap[$key]);
            if ($wanted === '' || $wanted === '/dev/null') {
                continue;
            }

            // Replace only the "+++ <path>" line of that file patch.
            // Safer: do replacement inside the file chunk and then replace chunk in full patch.
            $newChunk = preg_replace(
                '/^\+\+\+ .+$/m',
                '+++ '.$wanted,
                $fp->diff,
                1
            );

            if (!is_string($newChunk) || $newChunk === $fp->diff) {
                continue;
            }

            $patchText = str_replace($fp->diff, $newChunk, $patchText);
        }

        return $patchText;
    }

    private function normalizeRelativePath(string $path) : string
    {
        $path = trim($path);
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        // collapse . and ..
        $segments = [];
        foreach (explode('/', $path) as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $seg;
        }

        return implode('/', $segments);
    }
}
