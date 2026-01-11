<?php

namespace App\Patch;

use App\Entity\Project;
use App\Filesystem\SentinelFilesystem;

final readonly class PatchApplyService
{
    public function __construct(
        private UnifiedDiffParser $parser,
        private UnifiedDiffApplier $applier,
        private SentinelFilesystem $fs
    ) {
    }

    /**
     * @param array<string, string> $selectedHashes map path => sha256
     *
     * @return array{applied:list<array>, errors:list<string>}
     */
    public function apply(Project $project, string $patchText, array $selectedHashes) : array
    {
        $filePatches = $this->parser->parse($patchText);

        $applied = [];
        $errors = [];

        foreach ($filePatches as $fp) {
            try {
                if ($fp->isNewFile) {
                    $this->applyCreate($project, $fp->newPath, $fp->diff);
                    $applied[] = ['op' => 'create', 'path' => $fp->newPath];
                    continue;
                }

                if ($fp->isDeletedFile) {
                    $this->applyDelete($project, $fp->oldPath, $selectedHashes);
                    $applied[] = ['op' => 'delete', 'path' => $fp->oldPath];
                    continue;
                }

                // Update existing file
                $this->applyUpdate($project, $fp->oldPath, $fp->diff, $selectedHashes);
                $applied[] = ['op' => 'update', 'path' => $fp->oldPath];
            } catch (\Throwable $e) {
                $errors[] = sprintf('%s → %s: %s', $fp->oldPath, $fp->newPath, $e->getMessage());
            }
        }

        return ['applied' => $applied, 'errors' => $errors];
    }

    private function applyCreate(Project $project, string $path, string $fileDiff) : void
    {
        // Ensure allowed path & resolve within project
        $abs = $this->resolve($project, $path);

        // Extract content from diff (+ lines only)
        $content = $this->extractNewFileContent($fileDiff);

        $dir = dirname($abs);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Cannot create directory: '.$dir);
        }

        if (file_exists($abs)) {
            throw new \RuntimeException('File already exists.');
        }

        $tmp = $abs.'.sentinel.tmp.'.bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $content) === false) {
            throw new \RuntimeException('Cannot write temp file.');
        }

        if (!@rename($tmp, $abs)) {
            @unlink($tmp);
            throw new \RuntimeException('Cannot move temp file into place.');
        }
    }

    private function applyDelete(Project $project, string $path, array $selectedHashes) : void
    {
        $this->assertSelectedAndUnchanged($project, $path, $selectedHashes);

        $abs = $this->resolve($project, $path);
        if (!is_file($abs)) {
            throw new \RuntimeException('File not found.');
        }

        if (!@unlink($abs)) {
            throw new \RuntimeException('Cannot delete file.');
        }
    }

    private function applyUpdate(Project $project, string $path, string $fileDiff, array $selectedHashes) : void
    {
        $this->assertSelectedAndUnchanged($project, $path, $selectedHashes);

        $abs = $this->resolve($project, $path);

        $original = (string) file_get_contents($abs);
        $updated = $this->applier->applyToString($original, $fileDiff);

        $tmp = $abs.'.sentinel.tmp.'.bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $updated) === false) {
            throw new \RuntimeException('Cannot write temp file.');
        }

        if (!@rename($tmp, $abs)) {
            @unlink($tmp);
            throw new \RuntimeException('Cannot replace file.');
        }
    }

    private function resolve(Project $project, string $relativePath) : string
    {
        $this->fs->assertAllowedRelativePath($relativePath);

        // We leverage SentinelFilesystem validation by calling preview/sha256,
        // but for create (file doesn't exist), we need to resolve ourselves safely:
        // easiest: use its internal constraints by calling list/sha256 is not possible.
        // So we reuse its root confinement by calling preview for existing paths only.
        // Here: do a conservative manual resolve similar to fs service.
        $root = rtrim(str_replace('\\', '/', $project->getPath()), '/');
        $rel = ltrim(str_replace('\\', '/', $relativePath), '/');

        // basic traversal collapse
        $segments = [];
        foreach (explode('/', $rel) as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $seg;
        }
        $rel = implode('/', $segments);

        $abs = $root.($rel === '' ? '' : '/'.$rel);

        // Final hard safety check
        $rootReal = realpath($root);
        if ($rootReal === false) {
            throw new \RuntimeException('Project root not found.');
        }
        $rootReal = rtrim(str_replace('\\', '/', $rootReal), '/');

        $absParent = realpath(dirname($abs));
        if ($absParent !== false) {
            $absParent = rtrim(str_replace('\\', '/', $absParent), '/');
            if (!str_starts_with($absParent.'/', $rootReal.'/')) {
                throw new \RuntimeException('Path escapes project root.');
            }
        }

        // Denylist check by asking fs to validate a path we can hash if exists.
        // For create, we can still call list on parent dir to trigger deny rules if you want,
        // but we keep it simple and rely on your deny patterns in fs endpoints.
        // If you want strictness: expose a "assertAllowedPath" method in SentinelFilesystem.
        return $abs;
    }

    private function extractNewFileContent(string $fileDiff) : string
    {
        $fileDiff = str_replace(["\r\n", "\r"], "\n", $fileDiff);
        $lines = explode("\n", $fileDiff);

        $inHunk = false;
        $out = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, '@@')) {
                $inHunk = true;
                continue;
            }
            if (!$inHunk) {
                continue;
            }
            if ($line === '\ No newline at end of file') {
                continue;
            }

            if ($line !== '' && $line[0] === '+') {
                // Skip the "+++" header line
                if (str_starts_with($line, '+++ ')) {
                    continue;
                }
                $out[] = substr($line, 1);
            } elseif ($line !== '' && $line[0] === ' ') {
                // for new files, context lines can appear as ' ' (rare); treat as content too
                $out[] = substr($line, 1);
            } else {
                // ignore deletions for /dev/null
            }
        }

        return implode("\n", $out)."\n";
    }

    private function assertSelectedAndUnchanged(Project $project, string $path, array $selectedHashes) : void
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if (!isset($selectedHashes[$path])) {
            throw new \RuntimeException('Refusing to modify unselected file: '.$path);
        }

        $current = $this->fs->sha256($project, $path);
        if (!hash_equals($selectedHashes[$path], $current)) {
            throw new \RuntimeException('File changed since selection (hash mismatch).');
        }
    }
}
