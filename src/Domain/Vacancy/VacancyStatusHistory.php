<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;

final class VacancyStatusHistory
{
    use NormalizesStrings;

    private(set) ?int $id = null;

    private(set) ?string $notes;

    private(set) \DateTimeImmutable $transitionedAt;

    public function __construct(
        private(set) Vacancy $vacancy,
        private(set) VacancyStatus $fromStatus,
        private(set) VacancyStatus $toStatus,
        ?string $notes,
        ?\DateTimeImmutable $transitionedAt = null,
        private(set) ?int $outboxRecordId = null,
    ) {
        $this->notes = self::optional($notes);
        $this->transitionedAt = $transitionedAt ?? new \DateTimeImmutable();
    }
}
