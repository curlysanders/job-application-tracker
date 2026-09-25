<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Command;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\ApplicationSource;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final readonly class SaveVacancy
{
    /**
     * @param list<string>                                $sourceUrls
     * @param list<string>                                $existingTechStackSlugs
     * @param list<array{name: string, category: string}> $newTechStacks
     */
    public function __construct(
        public int $userId,
        public ?int $vacancyId,
        public string $title,
        public ?int $companyId,
        public ?int $recruiterId,
        public ?string $location,
        public ?ApplicationSource $applicationSource,
        public array $sourceUrls,
        public ?string $howToApply,
        public ?ContractType $contractType,
        public ?\DateTimeImmutable $datePosted,
        public ?\DateTimeImmutable $deadline,
        public ?\DateTimeImmutable $dateApplied,
        public ?string $fullText,
        public ?string $requirements,
        public ?string $responsibilities,
        public ?string $preferredQualifications,
        public ?string $aboutJob,
        public ?string $aboutCompany,
        public ?string $compensationBenefits,
        public ?string $minimumSalary,
        public ?string $maximumSalary,
        public string $currencyCode,
        public ?WorkMode $workMode,
        public ?string $hybridDetails,
        public ?int $excitement,
        public array $existingTechStackSlugs,
        public array $newTechStacks,
    ) {
    }
}
