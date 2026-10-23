<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final class VacancyDraftData
{
    public ?string $title = null;
    public ?string $location = null;
    public ?ContractType $contractType = null;
    public ?\DateTimeImmutable $datePosted = null;
    public ?\DateTimeImmutable $deadline = null;
    public ?string $fullText = null;
    public ?string $requirements = null;
    public ?string $responsibilities = null;
    public ?string $preferredQualifications = null;
    public ?string $aboutJob = null;
    public ?string $aboutCompany = null;
    public ?string $compensationBenefits = null;
    public ?string $minimumSalary = null;
    public ?string $maximumSalary = null;
    public string $currencyCode = 'EUR';
    public ?WorkMode $workMode = null;
    public ?string $hybridDetails = null;
    public ?string $existingTags = null;
}
