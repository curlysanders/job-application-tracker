<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\User;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Shared\Money;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsDomainEvents;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsEvents;

final class User implements RecordsEvents
{
    use NormalizesStrings;
    use RecordsDomainEvents;

    private Identifier $id;

    private string $email = '';

    /** @var list<string> */
    private array $roles = [];

    private string $password = '';

    private \DateTimeImmutable $createdAt;

    private ?string $minimumPreferredSalary = null;

    private string $minimumPreferredSalaryCurrency = 'EUR';

    private ?int $maximumCommuteMinutes = null;

    private ?PreferredTransportMode $preferredTransportMode = null;

    private ?string $resumeStoragePath = null;

    private ?string $resumeOriginalFilename = null;

    private ?string $resumeMimeType = null;

    private ?\DateTimeImmutable $resumeUploadedAt = null;

    private ?string $pendingResumeStoragePath = null;

    private ?string $pendingResumeOriginalFilename = null;

    private ?string $pendingResumeMimeType = null;

    private ?\DateTimeImmutable $pendingResumeUploadedAt = null;

    private ?Identifier $resumeValidationId = null;

    private ?ResumeValidationStatus $resumeValidationStatus = null;

    private ?string $resumeValidationFailure = null;

    public function __construct()
    {
        $this->id = Identifier::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): Identifier
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
        if (false === filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        return $this;
    }

    public static function normalizeEmail(#[\SensitiveParameter] string $email): string
    {
        return mb_strtolower(trim($email));
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
        ResumeMetadata::assertValid($storagePath, $originalFilename, $mimeType);
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
        ResumeMetadata::assertValid($storagePath, $originalFilename, $mimeType);
        $previousPendingPath = $this->pendingResumeStoragePath;
        $this->pendingResumeStoragePath = $storagePath;
        $this->pendingResumeOriginalFilename = $originalFilename;
        $this->pendingResumeMimeType = $mimeType;
        $this->pendingResumeUploadedAt = $uploadedAt;
        $this->resumeValidationId = Identifier::v7();
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

    public function promotePendingResume(Identifier $validationId): ?string
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

    public function rejectPendingResume(Identifier $validationId, string $failure): ?string
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
