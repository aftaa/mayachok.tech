<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $oauthId = null;

    #[ORM\Column(length: 255)]
    private ?string $displayName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatarUrl = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthday = null;

    #[ORM\Column(type: 'bigint')]
    private int $storageUsed = 0;

    #[ORM\Column(type: 'bigint')]
    private int $storageLimit = 10737418240; // 10GB

    /**
     * @var Collection<int, Mix>
     */
    #[ORM\ManyToMany(targetEntity: Mix::class, inversedBy: 'favoritedBy')]
    #[ORM\JoinTable(name: 'user_favorite_mix')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'mix_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $favoriteMixes;

    public function __construct()
    {
        $this->favoriteMixes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getOauthId(): ?string
    {
        return $this->oauthId;
    }

    public function setOauthId(string $oauthId): static
    {
        $this->oauthId = $oauthId;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function getBirthday(): ?\DateTime
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTime $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getStorageUsed(): int
    {
        return $this->storageUsed;
    }

    public function setStorageUsed(int $storageUsed): static
    {
        $this->storageUsed = $storageUsed;

        return $this;
    }

    public function addStorageUsed(int $bytes): static
    {
        $this->storageUsed += $bytes;

        return $this;
    }

    public function subtractStorageUsed(int $bytes): static
    {
        $this->storageUsed -= $bytes;
        if ($this->storageUsed < 0) {
            $this->storageUsed = 0;
        }

        return $this;
    }

    public function getStorageLimit(): int
    {
        return $this->storageLimit;
    }

    public function setStorageLimit(int $storageLimit): static
    {
        $this->storageLimit = $storageLimit;

        return $this;
    }

    public function hasStorageSpace(int $bytes): bool
    {
        return ($this->storageUsed + $bytes) <= $this->storageLimit;
    }

    /**
     * @return Collection<int, Mix>
     */
    public function getFavoriteMixes(): Collection
    {
        return $this->favoriteMixes;
    }

    public function addFavoriteMix(Mix $favoriteMix): static
    {
        if (!$this->favoriteMixes->contains($favoriteMix)) {
            $this->favoriteMixes->add($favoriteMix);
        }

        return $this;
    }

    public function removeFavoriteMix(Mix $favoriteMix): static
    {
        $this->favoriteMixes->removeElement($favoriteMix);

        return $this;
    }

    public function hasFavoriteMix(Mix $mix): bool
    {
        return $this->favoriteMixes->contains($mix);
    }
}
