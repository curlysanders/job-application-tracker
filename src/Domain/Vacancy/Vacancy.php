<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'vacancies')]
#[ORM\Index(name: 'IDX_VACANCIES_STATUS', fields: ['status'])]
#[ORM\Index(name: 'IDX_VACANCIES_USER', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_VACANCIES_DATE_ADDED', fields: ['dateAdded'])]
final class Vacancy
{
    use NormalizesStrings;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Company $company = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Recruiter $recruiter = null;

    /** @var Collection<int, TechStack> */
    #[ORM\ManyToMany(targetEntity: TechStack::class)]
    #[ORM\JoinTable(name: 'vacancy_tech_stacks')]
    private Collection $techStacks;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $fullText = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $requirements = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $responsibilities = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $preferredQualifications = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aboutJob = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aboutCompany = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $compensationBenefits = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $sourceUrls = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $howToApply = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(name: 'min_salary', type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $minimumSalary = null;

    #[ORM\Column(name: 'max_salary', type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $maximumSalary = null;

    #[ORM\Column(length: 3)]
    private string $currencyCode = 'EUR';

    #[ORM\Column(length: 20, nullable: true, enumType: WorkMode::class)]
    private ?WorkMode $workMode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $hybridDetails = null;

    #[ORM\Column]
    private \DateTimeImmutable $dateAdded;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $datePosted = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deadline = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateApplied = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextActionAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nextActionTitle = null;

    #[ORM\Column(length: 20, enumType: VacancyStatus::class)]
    private VacancyStatus $status = VacancyStatus::Bookmarked;

    #[ORM\Column]
    private bool $archived = false;

    #[ORM\Column(nullable: true)]
    private ?int $excitement = null;

    #[ORM\Column(length: 20, nullable: true, enumType: ContractType::class)]
    private ?ContractType $contractType = null;

    #[ORM\Column(length: 20, nullable: true, enumType: ApplicationSource::class)]
    private ?ApplicationSource $applicationSource = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $terminalReason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $scratchpadNotes = null;

    public function __construct(
        #[ORM\ManyToOne]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user, string $title)
    {
        $this->title = self::required($title, 'A vacancy title is required.');
        $this->dateAdded = new \DateTimeImmutable();
        $this->techStacks = new ArrayCollection();
    }

    public function updateTitle(string $title): void
    {
        $this->title = self::required($title, 'A vacancy title is required.');
    }

    public function replaceSalaryRange(?SalaryRange $salaryRange): void
    {
        if (null === $salaryRange) {
            $this->minimumSalary = null;
            $this->maximumSalary = null;

            return;
        }

        $this->minimumSalary = $salaryRange->getMinimum()?->getAmount()->toString();
        $this->maximumSalary = (string) $salaryRange->getMaximum()?->getAmount()->toString();
        $this->currencyCode = $salaryRange->getCurrencyCode();
    }

    public function replaceTechStacks(TechStack ...$techStacks): void
    {
        $this->techStacks->clear();
        foreach ($techStacks as $techStack) {
            $this->techStacks->add($techStack);
        }
    }

    public function setExcitement(?int $excitement): void
    {
        if (null !== $excitement && (0 > $excitement || 5 < $excitement)) {
            throw new \InvalidArgumentException('Excitement must be between 0 and 5.');
        }

        $this->excitement = $excitement;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTitle(): string
    {
        return $this->title;
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

    public function getSalaryRange(): ?SalaryRange
    {
        return SalaryRange::fromDecimals($this->minimumSalary, $this->maximumSalary, $this->currencyCode);
    }

    /** @return Collection<int, TechStack> */
    public function getTechStacks(): Collection
    {
        return $this->techStacks;
    }

    public function getExcitement(): ?int
    {
        return $this->excitement;
    }
}
