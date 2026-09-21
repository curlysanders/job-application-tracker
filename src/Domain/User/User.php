<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'min_preferred_salary', type: 'gross_monthly_salary', precision: 10, scale: 2, nullable: true)]
    private ?GrossMonthlySalary $minimumPreferredSalary = null;

    #[ORM\Column(name: 'max_commute_minutes', nullable: true)]
    private ?int $maximumCommuteMinutes = null;

    #[ORM\Column(length: 20, nullable: true, enumType: PreferredTransportMode::class)]
    private ?PreferredTransportMode $preferredTransportMode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resumeStoragePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resumeOriginalFilename = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $resumeMimeType = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resumeUploadedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(#[\SensitiveParameter] string $email): self
    {
        $this->email = self::normalizeEmail($email);

        return $this;
    }

    public static function normalizeEmail(#[\SensitiveParameter] string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new \LogicException('A user must have an email address.');
        }

        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(#[\SensitiveParameter] string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatePreferences(
        ?GrossMonthlySalary $minimumPreferredSalary,
        ?int $maximumCommuteMinutes,
        ?PreferredTransportMode $preferredTransportMode,
    ): void {
        if (null !== $maximumCommuteMinutes && $maximumCommuteMinutes <= 0) {
            throw new \InvalidArgumentException('The maximum commute time must be positive.');
        }

        $this->minimumPreferredSalary = $minimumPreferredSalary;
        $this->maximumCommuteMinutes = $maximumCommuteMinutes;
        $this->preferredTransportMode = $preferredTransportMode;
    }

    public function getMinimumPreferredSalary(): ?GrossMonthlySalary
    {
        return $this->minimumPreferredSalary;
    }

    public function getMaximumCommuteMinutes(): ?int
    {
        return $this->maximumCommuteMinutes;
    }

    public function getPreferredTransportMode(): ?PreferredTransportMode
    {
        return $this->preferredTransportMode;
    }

    public function evaluatesSalary(GrossMonthlySalary $grossSalary): SalaryFitStatus
    {
        if (null === $this->minimumPreferredSalary) {
            return SalaryFitStatus::NotConfigured;
        }

        return $grossSalary->isAtLeast($this->minimumPreferredSalary)
            ? SalaryFitStatus::MeetsTarget
            : SalaryFitStatus::BelowTarget;
    }

    public function replaceResume(
        string $storagePath,
        string $originalFilename,
        string $mimeType,
        \DateTimeImmutable $uploadedAt,
    ): void {
        $this->resumeStoragePath = $storagePath;
        $this->resumeOriginalFilename = $originalFilename;
        $this->resumeMimeType = $mimeType;
        $this->resumeUploadedAt = $uploadedAt;
    }

    public function getResumeStoragePath(): ?string
    {
        return $this->resumeStoragePath;
    }

    public function getResumeOriginalFilename(): ?string
    {
        return $this->resumeOriginalFilename;
    }

    public function getResumeMimeType(): ?string
    {
        return $this->resumeMimeType;
    }

    public function getResumeUploadedAt(): ?\DateTimeImmutable
    {
        return $this->resumeUploadedAt;
    }
}
