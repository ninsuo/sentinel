<?php

namespace App\Filesystem\Dto;

final readonly class FilePreviewDto
{
    public function __construct(
        public string $path,
        public int $size,
        public string $sha256,
        public string $encoding,      // "utf-8" or "unknown"
        public bool $truncated,
        public string $content,
    ) {
    }

    public function toArray() : array
    {
        return [
            'path' => $this->path,
            'size' => $this->size,
            'sha256' => $this->sha256,
            'encoding' => $this->encoding,
            'truncated' => $this->truncated,
            'content' => $this->content,
        ];
    }
}
