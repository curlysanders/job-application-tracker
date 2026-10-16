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

    private Identifier $id;

    private ?Company $company = null;

    private ?Recruiter $recruiter = null;

    /** @var list<TechStack>|(\ArrayAccess<int, TechStack>&\Traversable<int, TechStack>) */
    private array|(\ArrayAccess&\Traversable) $techStacks;

    private string $title;

    private ?string $fullText = null;

    private ?string $requirements = null;

    private ?string $responsibilities = null;

    private ?string $preferredQualifications = null;

    private ?string $aboutJob = null;

    private ?string $aboutCompany = null;

    private ?string $compensationBenefits = null;

    /** @var list<string> */
    private array $sourceUrls = [];

    private ?string $howToApply = null;

    private ?string $location = null;

    private ?string $minimumSalary = null;

    private ?string $maximumSalary = null;

    private string $currencyCode = 'EUR';

    private ?WorkMode $workMode = null;

    private ?string $hybridDetails = null;

    private \DateTimeImmutable $dateAdded;

    private ?\DateTimeImmutable $datePosted = null;

    private ?\DateTimeImmutable $deadline = null;

    private ?\DateTimeImmutable $dateApplied = null;

    private ?\DateTimeImmutable $nextActionAt = null;

    private ?string $nextActionTitle = null;

    private VacancyStatus $status = VacancyStatus::Bookmarked;

    private bool $archived = false;

    private ?int $excitement = null;

    private ?ContractType $contractType = null;

    private ?ApplicationSource $applicationSource = null;

    /** @phpstan-ignore-next-line Persisted terminal state is populated by workflow infrastructure. */
    private ?string $terminalReason = null;

    private ?string $scratchpadNotes = null;

    public function __construct(private User $user, string $title)
    {
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

        $this->minimumSalary = $salaryRange->getMinimum()?->amount();
        $this->maximumSalary = $salaryRange->getMaximum()?->amount();
        $this->currencyCode = $salaryRange->getCurrencyCode();
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

    public function updateNextAction(?string $title, ?\DateTimeImmutable $at): void
    {
        $title = self::optional($title);
        if (null === $at && null !== $title) {
            throw new \InvalidArgumentException('A next action title requires a date and time.');
        }

        $this->nextActionTitle = $title;
        $this->nextActionAt = $at;
        $this->recordEvent(new VacancyNextActionUpdated($this->id, []));
    }

    public function getId(): Identifier
    {
        return $this->id;
    }

    public function recordCreated(): void
    {
        $this->recordEvent(new VacancyCreated($this->id, $this->lifecycleDetails()));
    }

    public function recordDetailsUpdated(): void
    {
        $this->recordEvent(new VacancyDetailsUpdated($this->id, $this->lifecycleDetails()));
    }

    public function recordStatusTransitioned(VacancyStatus $from, string $transition, ?string $note): void
    {
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getTerminalReason(): ?string
    {
        return $this->terminalReason;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function getRecruiter(): ?Recruiter
    {
        return $this->recruiter;
    }

    public function getFullText(): ?string
    {
        return $this->fullText;
    }

    public function getRequirements(): ?string
    {
        return $this->requirements;
    }

    public function getResponsibilities(): ?string
    {
        return $this->responsibilities;
    }

    public function getPreferredQualifications(): ?string
    {
        return $this->preferredQualifications;
    }

    public function getAboutJob(): ?string
    {
        return $this->aboutJob;
    }

    public function getAboutCompany(): ?string
    {
        return $this->aboutCompany;
    }

    public function getCompensationBenefits(): ?string
    {
        return $this->compensationBenefits;
    }

    /** @return list<string> */
    public function getSourceUrls(): array
    {
        return $this->sourceUrls;
    }

    public function getHowToApply(): ?string
    {
        return $this->howToApply;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getWorkMode(): ?WorkMode
    {
        return $this->workMode;
    }

    public function getHybridDetails(): ?string
    {
        return $this->hybridDetails;
    }

    public function getDatePosted(): ?\DateTimeImmutable
    {
        return $this->datePosted;
    }

    public function getDeadline(): ?\DateTimeImmutable
    {
        return $this->deadline;
    }

    public function getDateApplied(): ?\DateTimeImmutable
    {
        return $this->dateApplied;
    }

    public function getContractType(): ?ContractType
    {
        return $this->contractType;
    }

    public function getApplicationSource(): ?ApplicationSource
    {
        return $this->applicationSource;
    }

    public function getStatus(): VacancyStatus
    {
        return $this->status;
    }

    public function setStatus(VacancyStatus $status): void
    {
        $this->status = $status;
    }

    public function getDateAdded(): \DateTimeImmutable
    {
        return $this->dateAdded;
    }

    public function isArchived(): bool
    {
        return $this->archived;
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

    /** @return iterable<int, TechStack> */
    public function getTechStacks(): iterable
    {
        return $this->techStacks;
    }

    public function getExcitement(): ?int
    {
        return $this->excitement;
    }

    public function getNextActionAt(): ?\DateTimeImmutable
    {
        return $this->nextActionAt;
    }

    public function getNextActionTitle(): ?string
    {
        return $this->nextActionTitle;
    }

    public function getScratchpadNotes(): ?string
    {
        return $this->scratchpadNotes;
    }
}
