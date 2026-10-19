<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsDomainEvents;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsEvents;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\User;

final class Vacancy implements RecordsEvents
{
    use NormalizesStrings;
    use RecordsDomainEvents;

    private(set) Identifier $id;

    private(set) ?Company $company = null;

    private(set) ?Recruiter $recruiter = null;

    /** @var list<TechStack>|(\ArrayAccess<int, TechStack>&\Traversable<int, TechStack>) */
    private(set) array|(\ArrayAccess&\Traversable) $techStacks;

    private(set) string $title;

    private(set) ?string $fullText = null;

    private(set) ?string $requirements = null;

    private(set) ?string $responsibilities = null;

    private(set) ?string $preferredQualifications = null;

    private(set) ?string $aboutJob = null;

    private(set) ?string $aboutCompany = null;

    private(set) ?string $compensationBenefits = null;

    /** @var list<string> */
    private(set) array $sourceUrls = [];

    private(set) ?string $howToApply = null;

    private(set) ?string $location = null;

    private(set) ?string $minimumSalary = null;

    private(set) ?string $maximumSalary = null;

    private(set) string $currencyCode = 'EUR';

    private(set) ?WorkMode $workMode = null;

    private(set) ?string $hybridDetails = null;

    private(set) \DateTimeImmutable $dateAdded;

    private(set) ?\DateTimeImmutable $datePosted = null;

    private(set) ?\DateTimeImmutable $deadline = null;

    private(set) ?\DateTimeImmutable $dateApplied = null;

    private(set) ?\DateTimeImmutable $nextActionAt = null;

    private(set) ?string $nextActionTitle = null;

    private(set) VacancyStatus $status = VacancyStatus::Bookmarked;

    private(set) bool $archived = false;

    private(set) ?int $excitement = null;

    private(set) ?ContractType $contractType = null;

    private(set) ?ApplicationSource $applicationSource = null;

    private(set) ?string $terminalReason = null;

    private(set) ?string $scratchpadNotes = null;

    public function __construct(
        public readonly User $user,
        string $title,
    ) {
        $this->id = Identifier::v7();
        $this->title = self::required($title, 'A vacancy title is required.');
        $this->dateAdded = new \DateTimeImmutable();
        $this->techStacks = [];
    }

    public function updateTitle(string $title): void
    {
        $this->title = self::required($title, 'A vacancy title is required.');
    }

    /** @param list<string> $sourceUrls */
    public function updateAuthoringDetails(
        ?Company $company,
        ?Recruiter $recruiter,
        ?string $fullText,
        ?string $requirements,
        ?string $responsibilities,
        ?string $preferredQualifications,
        ?string $aboutJob,
        ?string $aboutCompany,
        ?string $compensationBenefits,
        array $sourceUrls,
        ?string $howToApply,
        ?string $location,
        ?WorkMode $workMode,
        ?string $hybridDetails,
        ?\DateTimeImmutable $datePosted,
        ?\DateTimeImmutable $deadline,
        ?\DateTimeImmutable $dateApplied,
        ?ContractType $contractType,
        ?ApplicationSource $applicationSource,
    ): void {
        $this->company = $company;
        $this->recruiter = $recruiter;
        $this->fullText = self::optional($fullText);
        $this->requirements = self::optional($requirements);
        $this->responsibilities = self::optional($responsibilities);
        $this->preferredQualifications = self::optional($preferredQualifications);
        $this->aboutJob = self::optional($aboutJob);
        $this->aboutCompany = self::optional($aboutCompany);
        $this->compensationBenefits = self::optional($compensationBenefits);
        $this->sourceUrls = array_map(
            static fn (string $url): string => self::required($url, 'A source URL is required.'),
            $sourceUrls,
        );
        $this->howToApply = self::optional($howToApply);
        $this->location = self::optional($location);
        $this->workMode = $workMode;
        $this->hybridDetails = self::optional($hybridDetails);
        $this->datePosted = $datePosted;
        $this->deadline = $deadline;
        $this->dateApplied = $dateApplied;
        $this->contractType = $contractType;
        $this->applicationSource = $applicationSource;
    }

    public function replaceSalaryRange(?SalaryRange $salaryRange): void
    {
        if (null === $salaryRange) {
            $this->minimumSalary = null;
            $this->maximumSalary = null;

            return;
        }

        $this->minimumSalary = $salaryRange->minimum?->amount();
        $this->maximumSalary = $salaryRange->maximum?->amount();
        $this->currencyCode = $salaryRange->currencyCode;
    }

    public function replaceTechStacks(TechStack ...$techStacks): void
    {
        if (is_array($this->techStacks)) {
            $this->techStacks = array_values($techStacks);

            return;
        }

        foreach ($this->techStacks as $key => $techStack) {
            unset($this->techStacks[$key]);
        }
        foreach ($techStacks as $techStack) {
            $this->techStacks[] = $techStack;
        }
    }

    public function setExcitement(?int $excitement): void
    {
        if (null !== $excitement && (0 > $excitement || 5 < $excitement)) {
            throw new \InvalidArgumentException('Excitement must be between 0 and 5.');
        }

        $this->excitement = $excitement;
    }

    public function updateScratchpadNotes(?string $scratchpadNotes): void
    {
        $this->scratchpadNotes = self::optional($scratchpadNotes);
        $this->recordEvent(new VacancyScratchpadUpdated($this->id, []));
    }

    public function updateNextAction(
        ?string $title,
        ?\DateTimeImmutable $at,
    ): void {
        $title = self::optional($title);
        if (null === $at && null !== $title) {
            throw new \InvalidArgumentException('A next action title requires a date and time.');
        }

        $this->nextActionTitle = $title;
        $this->nextActionAt = $at;
        $this->recordEvent(new VacancyNextActionUpdated($this->id, []));
    }

    public function recordCreated(): void
    {
        $this->recordEvent(new VacancyCreated($this->id, $this->lifecycleDetails()));
    }

    public function recordDetailsUpdated(): void
    {
        $this->recordEvent(new VacancyDetailsUpdated($this->id, $this->lifecycleDetails()));
    }

    public function recordStatusTransitioned(
        VacancyStatus $from,
        string $transition,
        ?string $note,
    ): void {
        $this->recordEvent(new VacancyStatusTransitioned($this->id, [
            'fromStatus' => $from->value,
            'toStatus' => $this->status->value,
            'transition' => $transition,
            'note' => self::optional($note),
        ]));
    }

    public function recordDeleted(): void
    {
        $this->recordEvent(new VacancyDeleted($this->id, []));
    }

    /** @return array<string, mixed> */
    private function lifecycleDetails(): array
    {
        return [
            'status' => $this->status->value,
            'workMode' => $this->workMode?->value,
            'contractType' => $this->contractType?->value,
            'applicationSource' => $this->applicationSource?->value,
        ];
    }

    public function setStatus(VacancyStatus $status): void
    {
        $this->status = $status;
    }

    public function archive(): void
    {
        $this->archived = true;
        $this->recordEvent(new VacancyArchived($this->id, ['archived' => true]));
    }

    public function restore(): void
    {
        $this->archived = false;
        $this->recordEvent(new VacancyRestored($this->id, ['archived' => false]));
    }

    public function getSalaryRange(): ?SalaryRange
    {
        return SalaryRange::fromDecimals($this->minimumSalary, $this->maximumSalary, $this->currencyCode);
    }
}
