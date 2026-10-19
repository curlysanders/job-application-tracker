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

    private(set) Identifier $id;

    private(set) string $email = '';

    /** @var list<string> */
    private array $roles = [];

    private(set) string $password = '';

    private(set) \DateTimeImmutable $createdAt;

    private(set) ?string $minimumPreferredSalary = null;

    private(set) string $minimumPreferredSalaryCurrency = 'EUR';

    private(set) ?int $maximumCommuteMinutes = null;

    private(set) ?PreferredTransportMode $preferredTransportMode = null;

    private(set) ?string $resumeStoragePath = null;

    private(set) ?string $resumeOriginalFilename = null;

    private(set) ?string $resumeMimeType = null;

    private(set) ?\DateTimeImmutable $resumeUploadedAt = null;

    private ?string $pendingResumeStoragePath = null;

    private ?string $pendingResumeOriginalFilename = null;

    private ?string $pendingResumeMimeType = null;

    private ?\DateTimeImmutable $pendingResumeUploadedAt = null;

    private ?Identifier $resumeValidationId = null;

    private(set) ?ResumeValidationStatus $resumeValidationStatus = null;

    private(set) ?string $resumeValidationFailure = null;

    public function __construct()
    {
        $this->id = Identifier::v7();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function setEmail(
        #[\SensitiveParameter]
        string $email,
    ): self {
        $this->email = self::normalizeEmail($email);
        if (false === filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        return $this;
    }

    public static function normalizeEmail(
        #[\SensitiveParameter]
        string $email,
    ): string {
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

    public function setPassword(
        #[\SensitiveParameter]
        string $password,
    ): self {
        $this->password = $password;

        return $this;
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
        $this->minimumPreferredSalaryCurrency = $preferredSalary->currencyCode;
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

    public function evaluatesSalary(
        Money $grossSalary,
        ?CurrencyConversionRate $conversionRate = null,
    ): SalaryFitStatus {
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

    public function rejectPendingResume(
        Identifier $validationId,
        string $failure,
    ): ?string {
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
