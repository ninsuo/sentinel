<?php

namespace App\Filesystem;

final readonly class SentinelFilesystemConfig
{
    /**
     * @param list<string> $forbiddenPathPatterns Regex patterns applied on the normalized relative path (with /).
     */
    public function __construct(
        public int $maxPreviewBytes,
        public int $maxTreeEntries,
        public int $maxSearchResults,
        public array $forbiddenPathPatterns,
    ) {
    }
}
