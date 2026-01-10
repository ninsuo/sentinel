<?php

namespace App\Filesystem\Dto;

final readonly class DirectoryEntryDto
{
    public function __construct(
        public string $path,          // relative, unix-style (e.g. "src/Controller/HomeController.php")
        public string $name,          // basename
        public bool $isDir,
        public ?int $size,            // null for dirs
        public ?int $mtime,           // unix timestamp
    )
    {
    }

    public function toArray() : array
    {
        return [
            'path' => $this->path,
            'name' => $this->name,
            'isDir' => $this->isDir,
            'size' => $this->size,
            'mtime' => $this->mtime,
        ];
    }
}
