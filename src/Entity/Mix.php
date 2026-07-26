<?php

namespace App\Entity;

use App\Repository\MixRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MixRepository::class)]
class Mix
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $artist = null;

    #[ORM\Column(length: 255)]
    private ?string $original_path = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $isProcessed = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $s3OriginalKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $s3StreamKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $peaksKey = null;

    #[ORM\Column(nullable: true)]
    private ?int $duration = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(string $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function getOriginalPath(): ?string
    {
        return $this->original_path;
    }

    public function setOriginalPath(string $original_path): static
    {
        $this->original_path = $original_path;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isProcessed(): ?bool
    {
        return $this->isProcessed;
    }

    public function setIsProcessed(bool $is_processed): static
    {
        $this->isProcessed = $is_processed;

        return $this;
    }

    public function getS3OriginalKey(): ?string
    {
        return $this->s3OriginalKey;
    }

    public function setS3OriginalKey(?string $s3OriginalKey): static
    {
        $this->s3OriginalKey = $s3OriginalKey;

        return $this;
    }

    public function getS3StreamKey(): ?string
    {
        return $this->s3StreamKey;
    }

    public function setS3StreamKey(?string $s3StreamKey): static
    {
        $this->s3StreamKey = $s3StreamKey;

        return $this;
    }

    public function getPeaksKey(): ?string
    {
        return $this->peaksKey;
    }

    public function setPeaksKey(?string $peaksKey): static
    {
        $this->peaksKey = $peaksKey;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }
}
