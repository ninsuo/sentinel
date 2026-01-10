<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'project')]
#[ORM\Index(columns: ['deleted_at'], name: 'idx_project_deleted_at')]
#[ORM\UniqueConstraint(name: 'uniq_project_name', columns: ['name'])]
#[ORM\HasLifecycleCallbacks]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 120)]
    private string $name;

    // Keep it long. People love deep folder structures.
    #[ORM\Column(type: Types::TEXT)]
    private string $path;

    // Project system prompt / instructions (project-scoped context rules)
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $prompt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function __construct(string $name, string $path)
    {
        $this->name = $name;
        $this->path = $path;

        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PrePersist]
    public function onPrePersist() : void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate() : void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function softDelete() : void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function restore() : void
    {
        $this->deletedAt = null;
    }

    public function isDeleted() : bool
    {
        return null !== $this->deletedAt;
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function setPath(string $path) : self
    {
        $this->path = $path;

        return $this;
    }

    public function getPrompt() : ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt) : self
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function getCreatedAt() : \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt() : \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt() : ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }
}
