<?php

namespace App\Entity;

use App\Repository\MixRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MixRepository::class)]
class Mix
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private ?string $uuid = null;

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

    #[ORM\Column]
    private ?bool $isPrivate = false;

    #[ORM\Column(nullable: true)]
    private ?int $originalSize = null;

    #[ORM\Column(nullable: true)]
    private ?int $mp3Size = null;

    #[ORM\Column(nullable: true)]
    private ?int $peaksSize = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoriteMixes')]
    private Collection $favoritedBy;

    public function __construct()
    {
        $this->uuid = Uuid::v4()->toRfc4122();
        $this->favoritedBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
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

    public function isPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    public function getOriginalSize(): ?int
    {
        return $this->originalSize;
    }

    public function setOriginalSize(?int $originalSize): static
    {
        $this->originalSize = $originalSize;

        return $this;
    }

    public function getMp3Size(): ?int
    {
        return $this->mp3Size;
    }

    public function setMp3Size(?int $mp3Size): static
    {
        $this->mp3Size = $mp3Size;

        return $this;
    }

    public function getPeaksSize(): ?int
    {
        return $this->peaksSize;
    }

    public function setPeaksSize(?int $peaksSize): static
    {
        $this->peaksSize = $peaksSize;

        return $this;
    }

    public function getTotalSize(): int
    {
        return ($this->originalSize ?? 0) + ($this->mp3Size ?? 0) + ($this->peaksSize ?? 0);
    }

    /**
     * @return Collection<int, User>
     */
    public function getFavoritedBy(): Collection
    {
        return $this->favoritedBy;
    }

    public function addFavoritedBy(User $favoritedBy): static
    {
        if (!$this->favoritedBy->contains($favoritedBy)) {
            $this->favoritedBy->add($favoritedBy);
            $favoritedBy->addFavoriteMix($this);
        }

        return $this;
    }

    public function removeFavoritedBy(User $favoritedBy): static
    {
        if ($this->favoritedBy->removeElement($favoritedBy)) {
            $favoritedBy->removeFavoriteMix($this);
        }

        return $this;
    }
}
