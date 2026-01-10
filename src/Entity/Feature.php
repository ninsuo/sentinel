<?php

namespace App\Entity;

use App\Repository\FeatureRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeatureRepository::class)]
#[ORM\Table(name: 'feature')]
#[ORM\Index(name: 'idx_feature_deleted_at', columns: ['deleted_at'])]
#[ORM\Index(name: 'idx_feature_project', columns: ['project_id'])]
#[ORM\HasLifecycleCallbacks]
class Feature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'features')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\Column(type: Types::STRING, length: 160)]
    private string $name;

    // The goal / spec that stays with the feature across runs
    #[ORM\Column(type: Types::TEXT)]
    private string $prompt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    /**
     * @var list<FeatureRun>
     */
    #[ORM\OneToMany(mappedBy: 'feature', targetEntity: FeatureRun::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private iterable $runs;

    public function __construct(Project $project, string $name, string $prompt)
    {
        $this->project = $project;
        $this->name = $name;
        $this->prompt = $prompt;

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

    public function getProject() : Project
    {
        return $this->project;
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

    public function getPrompt() : string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt) : self
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
