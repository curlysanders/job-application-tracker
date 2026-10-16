<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;

final class VacancyStatusHistory
{
    use NormalizesStrings;

    /** @phpstan-ignore-next-line Doctrine assigns this generated identifier. */
    private ?int $id = null;

    private ?string $notes;

    private \DateTimeImmutable $transitionedAt;

    public function __construct(private Vacancy $vacancy, private VacancyStatus $fromStatus, private VacancyStatus $toStatus,
        ?string $notes,
        ?\DateTimeImmutable $transitionedAt = null,
        private ?int $outboxRecordId = null,
    ) {
        $this->notes = self::optional($notes);
        $this->transitionedAt = $transitionedAt ?? new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVacancy(): Vacancy
    {
        return $this->vacancy;
    }

    public function getFromStatus(): VacancyStatus
    {
        return $this->fromStatus;
    }

    public function getToStatus(): VacancyStatus
    {
        return $this->toStatus;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getTransitionedAt(): \DateTimeImmutable
    {
        return $this->transitionedAt;
    }

    public function getOutboxRecordId(): ?int
    {
        return $this->outboxRecordId;
    }
}
