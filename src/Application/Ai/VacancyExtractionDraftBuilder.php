<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyDraftData;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final readonly class VacancyExtractionDraftBuilder
{
    public function __construct(
        private StandardTechnologyResolver $technologies,
        private CompanyRepository $companies,
        private RecruiterRepository $recruiters,
    ) {
    }

    /**
     * @param array<string, string> $selectedFields
     * @param list<string>          $selectedTechStacks
     */
    public function vacancyData(
        VacancyExtractionReview $review,
        array $selectedFields,
        array $selectedTechStacks,
    ): VacancyDraftData {
        $data = new VacancyDraftData();
        $data->fullText = $review->vacancyText;
        foreach ($selectedFields as $field => $key) {
            $proposal = $this->findFieldProposal($review, $field, $key);
            if (null === $proposal) {
                throw new \InvalidArgumentException('The selected extraction proposal is unavailable.');
            }
            $this->apply($data, $proposal);
        }

        $slugs = [];
        foreach (array_unique($selectedTechStacks) as $key) {
            foreach ($review->techStacks as $proposal) {
                if (hash_equals($proposal->key, $key)) {
                    $techStack = $this->technologies->find($proposal->value);
                    if (null === $techStack) {
                        throw new \InvalidArgumentException('The selected technology is unavailable.');
                    }
                    $slugs[] = $techStack->slug;
                    continue 2;
                }
            }
            throw new \InvalidArgumentException('The selected technology proposal is unavailable.');
        }
        $data->existingTags = implode(',', array_unique($slugs));

        return $data;
    }

    public function applyOrganizationCandidates(
        VacancyDraftData $data,
        VacancyExtractionReview $review,
        string $userId,
    ): void {
        $company = $review->companyEvidence[0] ?? null;
        if (null !== $company) {
            $data->proposedCompanyName = $company->value;
            $matches = array_values(array_filter($this->companies->searchOwnedBy($userId, $company->value), static fn ($item): bool => mb_strtolower(trim($item->name)) === mb_strtolower(trim($company->value))));
            if (1 === count($matches)) {
                $data->companyId = $matches[0]->id->toRfc4122();
            }
        }
        $recruiter = $review->recruiterEvidence[0] ?? null;
        if (null !== $recruiter) {
            $data->proposedRecruiterAgencyName = $recruiter->value;
            $matches = array_values(array_filter($this->recruiters->searchOwnedBy($userId, $recruiter->value), static fn ($item): bool => mb_strtolower(trim($item->agencyName)) === mb_strtolower(trim($recruiter->value))));
            if (1 === count($matches)) {
                $data->recruiterId = $matches[0]->id->toRfc4122();
            }
        }
    }

    private function findFieldProposal(
        VacancyExtractionReview $review,
        string $field,
        string $key,
    ): ?ReviewProposal {
        return array_find($review->fields[$field] ?? [], static fn ($proposal) => hash_equals($proposal->key, $key));
    }

    private function apply(
        VacancyDraftData $data,
        ReviewProposal $proposal,
    ): void {
        match ($proposal->field) {
            'contractType' => $data->contractType = ContractType::from($proposal->value),
            'workMode' => $data->workMode = WorkMode::from($proposal->value),
            'datePosted' => $data->datePosted = new \DateTimeImmutable($proposal->value),
            'deadline' => $data->deadline = new \DateTimeImmutable($proposal->value),
            'title' => $data->title = $proposal->value,
            'location' => $data->location = $proposal->value,
            'minimumSalary' => $data->minimumSalary = $proposal->value,
            'maximumSalary' => $data->maximumSalary = $proposal->value,
            'currencyCode' => $data->currencyCode = $proposal->value,
            'hybridDetails' => $data->hybridDetails = $proposal->value,
            'requirements' => $data->requirements = $proposal->value,
            'responsibilities' => $data->responsibilities = $proposal->value,
            'preferredQualifications' => $data->preferredQualifications = $proposal->value,
            'aboutJob' => $data->aboutJob = $proposal->value,
            'aboutCompany' => $data->aboutCompany = $proposal->value,
            'compensationBenefits' => $data->compensationBenefits = $proposal->value,
            default => throw new \LogicException('Unsupported vacancy extraction field.'),
        };
    }
}
