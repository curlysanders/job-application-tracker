<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vacancy_status_history')]
#[ORM\UniqueConstraint(name: 'UNIQ_VACANCY_STATUS_HISTORY_OUTBOX_RECORD', columns: ['outbox_record_id'])]
#[ORM\Index(name: 'IDX_VACANCY_STATUS_HISTORY_VACANCY_AT', columns: ['vacancy_id', 'transitioned_at'])]
final class VacancyStatusHistory
{
    use NormalizesStrings;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(name: 'transitioned_at')]
    private \DateTimeImmutable $transitionedAt;

    public function __construct(#[ORM\ManyToOne]
        #[ORM\JoinColumn(name: 'vacancy_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE', foreignKeyName: 'FK_VACANCY_STATUS_HISTORY_VACANCY')]
        private Vacancy $vacancy,

        #[ORM\Column(name: 'from_status', length: 20, enumType: VacancyStatus::class)]
        private VacancyStatus $fromStatus,

        #[ORM\Column(name: 'to_status', length: 20, enumType: VacancyStatus::class)]
        private VacancyStatus $toStatus,

        ?string $notes,
        ?\DateTimeImmutable $transitionedAt = null,
        #[ORM\Column(name: 'outbox_record_id', nullable: true)]
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
