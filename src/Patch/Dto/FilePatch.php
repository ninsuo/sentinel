<?php

namespace App\Patch\Dto;

final readonly class FilePatch
{
    public function __construct(
        public string $oldPath,   // e.g. /dev/null or src/Foo.php
        public string $newPath,   // e.g. src/Foo.php or /dev/null
        public string $diff,      // full per-file unified diff
        public bool $isNewFile,
        public bool $isDeletedFile,
    ) {
    }

    public function key() : string
    {
        // stable key for form mapping
        return hash('sha256', $this->oldPath.'|'.$this->newPath);
    }
}
