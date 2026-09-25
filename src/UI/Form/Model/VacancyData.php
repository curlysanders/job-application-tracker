<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model;

use CurlySanders\JobApplicationTracker\Domain\Vacancy\ApplicationSource;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;
use CurlySanders\JobApplicationTracker\UI\Form\Model\TechStack\TechStackTagsData;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class VacancyData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $title = null;
    public ?int $companyId = null;
    public ?int $recruiterId = null;
    #[Assert\Length(max: 255)]
    public ?string $location = null;
    public ?ApplicationSource $applicationSource = null;
    /** @var list<?string> */
    public array $sourceUrls = [];
    public ?string $howToApply = null;
    public ?ContractType $contractType = null;
    public ?\DateTimeImmutable $datePosted = null;
    public ?\DateTimeImmutable $deadline = null;
    public ?\DateTimeImmutable $dateApplied = null;
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
    #[Assert\Range(min: 0, max: 5)]
    public ?int $excitement = null;
    #[Assert\Valid]
    public TechStackTagsData $techStacks;

    public function __construct()
    {
        $this->techStacks = new TechStackTagsData();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        foreach ($this->sourceUrls as $index => $sourceUrl) {
            if (null !== $sourceUrl && '' !== trim($sourceUrl) && false === filter_var($sourceUrl, FILTER_VALIDATE_URL)) {
                $context->buildViolation('Enter a valid source URL.')->atPath(sprintf('sourceUrls[%d]', $index))->addViolation();
            }
        }
        foreach (['minimumSalary', 'maximumSalary'] as $field) {
            $value = $this->{$field};
            if (null !== $value && '' !== trim($value) && 1 !== preg_match('/^(?!0+(?:\.0+)?$)\d+(?:\.\d{1,2})?$/', $value)) {
                $context->buildViolation('Enter a positive amount with at most two decimal places.')->atPath($field)->addViolation();
            }
        }
        if (
            null !== $this->minimumSalary
            && '' !== trim($this->minimumSalary)
            && null !== $this->maximumSalary
            && '' !== trim($this->maximumSalary)
            && is_numeric($this->minimumSalary)
            && is_numeric($this->maximumSalary)
            && (float) $this->minimumSalary > (float) $this->maximumSalary
        ) {
            $context->buildViolation('The minimum salary cannot exceed the maximum salary.')->atPath('maximumSalary')->addViolation();
        }
    }
}
