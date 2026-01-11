<?php

namespace App\Entity;

use App\Repository\FeatureRunRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeatureRunRepository::class)]
#[ORM\Table(name: 'feature_run')]
#[ORM\Index(name: 'idx_feature_run_feature', columns: ['feature_id'])]
#[ORM\Index(name: 'idx_feature_run_created_at', columns: ['created_at'])]
class FeatureRun
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Feature::class, inversedBy: 'runs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Feature $feature;

    // The prompt actually used for the run (feature prompt + maybe extra user input later)
    #[ORM\Column(type: Types::TEXT)]
    private string $userPrompt;

    // Selected files metadata for the run (paths, hashes, maybe excerpts later)
    // JSON is nice, but TEXT keeps it dead-simple and portable.
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $selectedFilesJson = null;

    // Full AI request/response (for audit/debug). Could be huge, hence LONGTEXT.
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiRequestJson = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiResponseText = null;

    // Patch in unified diff format (or structured ops). Stored raw.
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $patchText = null;

    #[ORM\Column(type: Types::STRING, length: 40, nullable: true)]
    private ?string $status = null; // e.g. created, generated, applied, rejected, failed

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $durationMs = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(Feature $feature, string $userPrompt)
    {
        $this->feature = $feature;
        $this->userPrompt = $userPrompt;
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'created';
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getFeature() : Feature
    {
        return $this->feature;
    }

    public function getUserPrompt() : string
    {
        return $this->userPrompt;
    }

    public function setUserPrompt(string $userPrompt) : self
    {
        $this->userPrompt = $userPrompt;

        return $this;
    }

    public function getSelectedFilesJson() : ?string
    {
        return $this->selectedFilesJson;
    }

    public function setSelectedFilesJson(?string $selectedFilesJson) : self
    {
        $this->selectedFilesJson = $selectedFilesJson;

        return $this;
    }

    public function getAiRequestJson() : ?string
    {
        return $this->aiRequestJson;
    }

    public function setAiRequestJson(?string $aiRequestJson) : self
    {
        $this->aiRequestJson = $aiRequestJson;

        return $this;
    }

    public function getAiResponseText() : ?string
    {
        return $this->aiResponseText;
    }

    public function setAiResponseText(?string $aiResponseText) : self
    {
        $this->aiResponseText = $aiResponseText;

        return $this;
    }

    public function getPatchText() : ?string
    {
        return $this->patchText;
    }

    public function setPatchText(?string $patchText) : self
    {
        $this->patchText = $patchText;

        return $this;
    }

    public function getStatus() : ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status) : self
    {
        $this->status = $status;

        return $this;
    }

    public function getDurationMs() : ?int
    {
        return $this->durationMs;
    }

    public function setDurationMs(?int $durationMs) : self
    {
        $this->durationMs = $durationMs;

        return $this;
    }

    public function getCreatedAt() : \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function files() : array
    {
        if ($this->selectedFilesJson === null) {
            return [];
        }

        return json_decode($this->selectedFilesJson, true);
    }
}
