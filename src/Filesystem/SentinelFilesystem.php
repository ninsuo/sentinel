<?php

namespace App\Filesystem;

use App\Entity\Project;
use App\Filesystem\Dto\DirectoryEntryDto;
use App\Filesystem\Dto\FilePreviewDto;
use App\Filesystem\Exception\BinaryFileException;
use App\Filesystem\Exception\ForbiddenPathException;
use App\Filesystem\Exception\NotFoundException;

final readonly class SentinelFilesystem
{
    public function __construct(
        private SentinelFilesystemConfig $config,
    ) {
    }

    /**
     * List entries in a directory (relative to project root).
     *
     * @return list<DirectoryEntryDto>
     */
    public function list(Project $project, string $relativeDir = '') : array
    {
        $relativeDir = $this->normalizeRelativePath($relativeDir);

        $this->assertAllowedRelativePath($relativeDir === '' ? '.' : $relativeDir);

        $absDir = $this->resolveAbsolutePath($project, $relativeDir);

        if (!is_dir($absDir)) {
            throw new NotFoundException(sprintf('Directory not found: %s', $relativeDir));
        }

        $entries = [];
        $count = 0;

        $handle = opendir($absDir);
        if (false === $handle) {
            throw new NotFoundException(sprintf('Cannot open directory: %s', $relativeDir));
        }

        try {
            while (false !== ($name = readdir($handle))) {
                if ($name === '.' || $name === '..') {
                    continue;
                }

                $count++;
                if ($count > $this->config->maxTreeEntries) {
                    break; // don’t DOS yourself for fun
                }

                $childRel = $relativeDir === '' ? $name : ($relativeDir.'/'.$name);
                $childRel = $this->normalizeRelativePath($childRel);

                if (!$this->isAllowedRelativePath($childRel)) {
                    continue;
                }

                $childAbs = $this->resolveAbsolutePath($project, $childRel);

                $isDir = is_dir($childAbs);
                $size = $isDir ? null : (is_file($childAbs) ? filesize($childAbs) : null);
                $mtime = @filemtime($childAbs);
                $mtime = false === $mtime ? null : $mtime;

                $entries[] = new DirectoryEntryDto(
                    path: $childRel,
                    name: $name,
                    isDir: $isDir,
                    size: $size,
                    mtime: $mtime,
                );
            }
        } finally {
            closedir($handle);
        }

        // Stable ordering for UI (dirs first, then files, alphabetical)
        usort($entries, static function (DirectoryEntryDto $a, DirectoryEntryDto $b) : int {
            if ($a->isDir !== $b->isDir) {
                return $a->isDir ? -1 : 1;
            }

            return strcasecmp($a->name, $b->name);
        });

        return $entries;
    }

    private function normalizeRelativePath(string $path) : string
    {
        $path = trim($path);
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        // Collapse "." and ".."
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

    public function assertAllowedRelativePath(string $relativePath) : void
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $normalized = $relativePath === '' ? '.' : $relativePath;

        // Deny "." is ok, but anything else gets checked
        if ($normalized !== '.') {
            foreach ($this->config->forbiddenPathPatterns as $pattern) {
                if (@preg_match($pattern, $normalized) === 1) {
                    throw new ForbiddenPathException(sprintf('Forbidden path: %s', $relativePath));
                }
            }
        }
    }

    private function resolveAbsolutePath(Project $project, string $relativePath) : string
    {
        $root = $this->normalizeAbsoluteRoot($project->getPath());
        $relativePath = $this->normalizeRelativePath($relativePath);

        $abs = $root.($relativePath === '' || $relativePath === '.' ? '' : '/'.$relativePath);

        // realpath for the root must exist; child may not, so we validate by prefix check on normalized strings
        $rootReal = realpath($root);
        if (false === $rootReal || !is_dir($rootReal)) {
            throw new NotFoundException(sprintf('Project root does not exist: %s', $project->getPath()));
        }

        $rootReal = $this->normalizeAbsoluteRoot($rootReal);

        $absNormalized = $this->normalizeAbsoluteRoot($abs);

        // Prevent escape (../) and symlink tricks:
        // We do best-effort. For existing targets we can realpath, for non-existing we rely on normalization + denylist.
        $absReal = realpath($absNormalized);
        if (false !== $absReal) {
            $absReal = $this->normalizeAbsoluteRoot($absReal);
            if (!str_starts_with($absReal.'/', $rootReal.'/')) {
                throw new ForbiddenPathException('Path escapes project root.');
            }

            return $absReal;
        }

        // If it doesn't exist yet, ensure its normalized form still starts with root
        if (!str_starts_with($absNormalized.'/', $rootReal.'/')) {
            throw new ForbiddenPathException('Path escapes project root.');
        }

        return $absNormalized;
    }

    // ----------------------------
    // Internals
    // ----------------------------

    private function isAllowedRelativePath(string $relativePath) : bool
    {
        try {
            $this->assertAllowedRelativePath($relativePath);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function normalizeAbsoluteRoot(string $path) : string
    {
        $path = str_replace('\\', '/', $path);
        $path = rtrim($path, '/');

        return $path;
    }

    /**
     * Search by filename substring (case-insensitive) under a directory.
     *
     * @return list<DirectoryEntryDto>
     */
    public function search(Project $project, string $query, string $relativeDir = '') : array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $relativeDir = $this->normalizeRelativePath($relativeDir);
        $this->assertAllowedRelativePath($relativeDir === '' ? '.' : $relativeDir);

        $absDir = $this->resolveAbsolutePath($project, $relativeDir);

        if (!is_dir($absDir)) {
            throw new NotFoundException(sprintf('Directory not found: %s', $relativeDir));
        }

        $results = [];
        $max = $this->config->maxSearchResults;

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $fileInfo) {
            if (count($results) >= $max) {
                break;
            }

            /** @var \SplFileInfo $fileInfo */
            $absPath = $fileInfo->getPathname();

            $rel = $this->absoluteToRelative($project, $absPath);
            $rel = $this->normalizeRelativePath($rel);

            if (!$this->isAllowedRelativePath($rel)) {
                // If a directory is forbidden, you could prune recursion, but PHP’s iterator makes it annoying.
                continue;
            }

            $name = $fileInfo->getFilename();
            if (stripos($name, $query) === false) {
                continue;
            }

            $isDir = $fileInfo->isDir();
            $size = $isDir ? null : $fileInfo->getSize();
            $mtime = $fileInfo->getMTime();

            $results[] = new DirectoryEntryDto(
                path: $rel,
                name: $name,
                isDir: $isDir,
                size: $size,
                mtime: $mtime,
            );
        }

        // dirs first, then files, alphabetical
        usort($results, static function (DirectoryEntryDto $a, DirectoryEntryDto $b) : int {
            if ($a->isDir !== $b->isDir) {
                return $a->isDir ? -1 : 1;
            }

            return strcasecmp($a->path, $b->path);
        });

        return $results;
    }

    private function absoluteToRelative(Project $project, string $absPath) : string
    {
        $root = $this->normalizeAbsoluteRoot($project->getPath());
        $absPath = $this->normalizeAbsoluteRoot($absPath);

        if (str_starts_with($absPath.'/', $root.'/')) {
            return ltrim(substr($absPath, strlen($root)), '/');
        }

        return $absPath; // fallback, will be denied by root check later anyway
    }

    public function preview(Project $project, string $relativePath) : FilePreviewDto
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $this->assertAllowedRelativePath($relativePath);

        $absPath = $this->resolveAbsolutePath($project, $relativePath);

        if (!is_file($absPath)) {
            throw new NotFoundException(sprintf('File not found: %s', $relativePath));
        }

        $size = filesize($absPath);
        if (false === $size) {
            throw new NotFoundException(sprintf('Cannot read file size: %s', $relativePath));
        }

        // Binary detection (read a small chunk and look for NUL)
        $head = $this->readBytes($absPath, 0, min(8192, $size));
        if ($this->looksBinary($head)) {
            throw new BinaryFileException('Binary files are not previewable.');
        }

        $max = $this->config->maxPreviewBytes;
        $truncated = $size > $max;

        $contentBytes = $this->readBytes($absPath, 0, min($size, $max));

        // Normalize to UTF-8, strip nasty bytes
        [$contentUtf8, $encoding] = $this->normalizeTextToUtf8($contentBytes);

        $sha256 = hash_file('sha256', $absPath) ?: hash('sha256', $contentBytes);

        return new FilePreviewDto(
            path: $relativePath,
            size: $size,
            sha256: $sha256,
            encoding: $encoding,
            truncated: $truncated,
            content: $contentUtf8,
        );
    }

    private function readBytes(string $absPath, int $offset, int $length) : string
    {
        $fp = @fopen($absPath, 'rb');
        if (false === $fp) {
            throw new NotFoundException('Cannot open file.');
        }

        try {
            if ($offset > 0) {
                fseek($fp, $offset);
            }
            $data = fread($fp, $length);

            return $data === false ? '' : $data;
        } finally {
            fclose($fp);
        }
    }

    private function looksBinary(string $bytes) : bool
    {
        if ($bytes === '') {
            return false;
        }

        // NUL byte is usually a dead giveaway
        if (str_contains($bytes, "\0")) {
            return true;
        }

        // Heuristic: if too many non-text control chars, it's probably binary
        $len = strlen($bytes);
        $bad = 0;
        for ($i = 0; $i < $len; $i++) {
            $c = ord($bytes[$i]);
            if ($c === 9 || $c === 10 || $c === 13) { // tab, LF, CR
                continue;
            }
            if ($c < 32) {
                $bad++;
            }
        }

        return ($bad / max(1, $len)) > 0.02;
    }

    /**
     * @return array{0:string,1:string} [utf8Text, encodingLabel]
     */
    private function normalizeTextToUtf8(string $bytes) : array
    {
        // If it's already valid UTF-8, keep it.
        if ($bytes === '' || preg_match('//u', $bytes) === 1) {
            // Normalize line endings
            $text = str_replace(["\r\n", "\r"], "\n", $bytes);

            return [$text, 'utf-8'];
        }

        // Try common encodings
        $candidates = ['UTF-8', 'ISO-8859-1', 'Windows-1252'];
        foreach ($candidates as $enc) {
            $converted = @mb_convert_encoding($bytes, 'UTF-8', $enc);
            if (is_string($converted) && preg_match('//u', $converted) === 1) {
                $converted = str_replace(["\r\n", "\r"], "\n", $converted);
                // Strip weird formatting chars (Cf) and other invisibles
                $converted = (string) preg_replace('/[\p{Cf}]/u', '', $converted);

                return [$converted, strtolower($enc)];
            }
        }

        // Last resort: strip invalid bytes
        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $bytes);
        if (!is_string($clean)) {
            $clean = '';
        }

        $clean = str_replace(["\r\n", "\r"], "\n", $clean);
        $clean = (string) preg_replace('/[\p{Cf}]/u', '', $clean);

        return [$clean, 'unknown'];
    }

    public function sha256(Project $project, string $relativePath) : string
    {
        $relativePath = $this->normalizeRelativePath($relativePath);
        $this->assertAllowedRelativePath($relativePath);

        $absPath = $this->resolveAbsolutePath($project, $relativePath);

        if (!is_file($absPath)) {
            throw new NotFoundException(sprintf('File not found: %s', $relativePath));
        }

        return hash_file('sha256', $absPath) ?: hash('sha256', (string) @file_get_contents($absPath));
    }
}
