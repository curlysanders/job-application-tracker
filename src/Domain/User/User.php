<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use Brick\Money\Money;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsDomainEvents;
use Doctrine\ORM\Mapping as ORM;
use Lingoda\DomainEventsBundle\Domain\Model\DomainEventAware;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User implements UserInterface, PasswordAuthenticatedUserInterface, DomainEventAware
{
    use NormalizesStrings;
    use RecordsDomainEvents;

    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

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

    #[ORM\Column(name: 'min_preferred_salary', type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $minimumPreferredSalary = null;

    #[ORM\Column(name: 'min_preferred_salary_currency', length: 3)]
    private string $minimumPreferredSalaryCurrency = 'EUR';

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

    #[ORM\Column(name: 'pending_resume_storage_path', length: 255, nullable: true)]
    private ?string $pendingResumeStoragePath = null;

    #[ORM\Column(name: 'pending_resume_original_filename', length: 255, nullable: true)]
    private ?string $pendingResumeOriginalFilename = null;

    #[ORM\Column(name: 'pending_resume_mime_type', length: 100, nullable: true)]
    private ?string $pendingResumeMimeType = null;

    #[ORM\Column(name: 'pending_resume_uploaded_at', nullable: true)]
    private ?\DateTimeImmutable $pendingResumeUploadedAt = null;

    #[ORM\Column(name: 'resume_validation_id', type: UuidType::NAME, nullable: true)]
    private ?Uuid $resumeValidationId = null;

    #[ORM\Column(name: 'resume_validation_status', length: 20, nullable: true, enumType: ResumeValidationStatus::class)]
    private ?ResumeValidationStatus $resumeValidationStatus = null;

    #[ORM\Column(name: 'resume_validation_failure', length: 255, nullable: true)]
    private ?string $resumeValidationFailure = null;

    public function __construct()
    {
        $this->id = Uuid::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
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
        PreferredSalary $preferredSalary,
        ?int $maximumCommuteMinutes,
        ?PreferredTransportMode $preferredTransportMode,
    ): void {
        if (null !== $maximumCommuteMinutes && $maximumCommuteMinutes <= 0) {
            throw new \InvalidArgumentException('The maximum commute time must be positive.');
        }

        $this->minimumPreferredSalary = $preferredSalary->getMinimumDecimal();
        $this->minimumPreferredSalaryCurrency = $preferredSalary->getCurrencyCode();
        $this->maximumCommuteMinutes = $maximumCommuteMinutes;
        $this->preferredTransportMode = $preferredTransportMode;
        $this->recordEvent(new UserPreferencesUpdated($this->id, []));
    }

    public function getPreferredSalary(): PreferredSalary
    {
        return PreferredSalary::fromDecimal(
            $this->minimumPreferredSalary,
            $this->minimumPreferredSalaryCurrency,
        );
    }

    public function getMaximumCommuteMinutes(): ?int
    {
        return $this->maximumCommuteMinutes;
    }

    public function getPreferredTransportMode(): ?PreferredTransportMode
    {
        return $this->preferredTransportMode;
    }

    public function evaluatesSalary(Money $grossSalary, ?CurrencyConversionRate $conversionRate = null): SalaryFitStatus
    {
        return $this->getPreferredSalary()->evaluate($grossSalary, $conversionRate);
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
        $this->recordEvent(new ResumeReplaced($this->id, []));
    }

    public function stageResume(
        string $storagePath,
        string $originalFilename,
        string $mimeType,
        \DateTimeImmutable $uploadedAt,
    ): ?string {
        $previousPendingPath = $this->pendingResumeStoragePath;
        $this->pendingResumeStoragePath = $storagePath;
        $this->pendingResumeOriginalFilename = $originalFilename;
        $this->pendingResumeMimeType = $mimeType;
        $this->pendingResumeUploadedAt = $uploadedAt;
        $this->resumeValidationId = Uuid::v7();
        $this->resumeValidationStatus = ResumeValidationStatus::Pending;
        $this->resumeValidationFailure = null;
        $this->recordEvent(new ResumeValidationRequested($this->id, [
            'validationId' => $this->resumeValidationId->toRfc4122(),
        ]));

        return $previousPendingPath;
    }

    public function getPendingResume(): ?PendingResume
    {
        if (
            ResumeValidationStatus::Pending !== $this->resumeValidationStatus
            || null === $this->resumeValidationId
            || null === $this->pendingResumeStoragePath
            || null === $this->pendingResumeOriginalFilename
            || null === $this->pendingResumeMimeType
            || null === $this->pendingResumeUploadedAt
        ) {
            return null;
        }

        return new PendingResume(
            $this->resumeValidationId,
            $this->pendingResumeStoragePath,
            $this->pendingResumeOriginalFilename,
            $this->pendingResumeMimeType,
            $this->pendingResumeUploadedAt,
        );
    }

    public function promotePendingResume(Uuid $validationId): ?string
    {
        $pendingResume = $this->getPendingResume();
        if (null === $pendingResume || !$pendingResume->validationId->equals($validationId)) {
            return null;
        }

        $previousPath = $this->resumeStoragePath;
        $this->resumeStoragePath = $pendingResume->storagePath;
        $this->resumeOriginalFilename = $pendingResume->originalFilename;
        $this->resumeMimeType = $pendingResume->mimeType;
        $this->resumeUploadedAt = $pendingResume->uploadedAt;
        $this->clearPendingResume();
        $this->resumeValidationStatus = ResumeValidationStatus::Validated;
        $this->recordEvent(new ResumeReplaced($this->id, []));

        return $previousPath;
    }

    public function rejectPendingResume(Uuid $validationId, string $failure): ?string
    {
        $pendingResume = $this->getPendingResume();
        if (null === $pendingResume || !$pendingResume->validationId->equals($validationId)) {
            return null;
        }

        $storagePath = $pendingResume->storagePath;
        $this->clearPendingResume();
        $this->resumeValidationId = $validationId;
        $this->resumeValidationStatus = ResumeValidationStatus::Failed;
        $this->resumeValidationFailure = self::required($failure, 'A resume validation failure must be provided.');

        return $storagePath;
    }

    public function recordRegistered(): void
    {
        $this->recordEvent(new UserRegistered($this->id, []));
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

    public function getResumeValidationStatus(): ?ResumeValidationStatus
    {
        return $this->resumeValidationStatus;
    }

    public function getResumeValidationFailure(): ?string
    {
        return $this->resumeValidationFailure;
    }

    private function clearPendingResume(): void
    {
        $this->pendingResumeStoragePath = null;
        $this->pendingResumeOriginalFilename = null;
        $this->pendingResumeMimeType = null;
        $this->pendingResumeUploadedAt = null;
        $this->resumeValidationId = null;
        $this->resumeValidationFailure = null;
    }
}
