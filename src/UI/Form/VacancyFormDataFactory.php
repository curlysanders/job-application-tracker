<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\SaveVacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\UI\Form\Model\VacancyData;

final class VacancyFormDataFactory
{
    public function fromVacancy(Vacancy $vacancy): VacancyData
    {
        $data = new VacancyData();
        $data->title = $vacancy->title;
        $data->companyId = $vacancy->company?->id->toRfc4122();
        $data->recruiterId = $vacancy->recruiter?->id->toRfc4122();
        $data->location = $vacancy->location;
        $data->applicationSource = $vacancy->applicationSource;
        $data->sourceUrls = $vacancy->sourceUrls;
        $data->howToApply = $vacancy->howToApply;
        $data->contractType = $vacancy->contractType;
        $data->datePosted = $vacancy->datePosted;
        $data->deadline = $vacancy->deadline;
        $data->dateApplied = $vacancy->dateApplied;
        $data->fullText = $vacancy->fullText;
        $data->requirements = $vacancy->requirements;
        $data->responsibilities = $vacancy->responsibilities;
        $data->preferredQualifications = $vacancy->preferredQualifications;
        $data->aboutJob = $vacancy->aboutJob;
        $data->aboutCompany = $vacancy->aboutCompany;
        $data->compensationBenefits = $vacancy->compensationBenefits;
        $data->minimumSalary = $vacancy->getSalaryRange()?->minimum?->amount();
        $data->maximumSalary = $vacancy->getSalaryRange()?->maximum?->amount();
        $data->currencyCode = $vacancy->getSalaryRange()->currencyCode ?? 'EUR';
        $data->workMode = $vacancy->workMode;
        $data->hybridDetails = $vacancy->hybridDetails;
        $data->excitement = $vacancy->excitement;
        $data->techStacks->existingTags = $vacancy->techStacks
                |> iterator_to_array(...)
                |> (static fn ($x) => array_map(static fn ($techStack): string => $techStack->slug, $x))
                |> (static fn ($x) => implode(',', $x));

        return $data;
    }

    public function command(
        string $userId,
        ?string $vacancyId,
        VacancyData $data,
    ): SaveVacancy {
        $newTechStacks = [];
        foreach ($data->techStacks->newTags as $newTechStack) {
            if (null === $newTechStack) {
                continue;
            }

            if (null === $newTechStack->name || null === $newTechStack->category) {
                throw new \LogicException('A valid new technology requires a name and category.');
            }
            $newTechStacks[] = ['name' => $newTechStack->name, 'category' => $newTechStack->category];
        }

        return new SaveVacancy(
            $userId,
            $vacancyId,
            $data->title ?? '',
            $data->companyId,
            $data->recruiterId,
            $data->location,
            $data->applicationSource,
            $this->sourceUrls($data),
            $data->howToApply,
            $data->contractType,
            $data->datePosted,
            $data->deadline,
            $data->dateApplied,
            $data->fullText,
            $data->requirements,
            $data->responsibilities,
            $data->preferredQualifications,
            $data->aboutJob,
            $data->aboutCompany,
            $data->compensationBenefits,
            $data->minimumSalary,
            $data->maximumSalary,
            strtoupper(trim($data->currencyCode)),
            $data->workMode,
            $data->hybridDetails,
            $data->excitement,
            $this->existingSlugs($data),
            $newTechStacks,
        );
    }

    /** @return list<string> */
    private function sourceUrls(VacancyData $data): array
    {
        return array_map(static fn (?string $url): string => trim((string) $url), $data->sourceUrls)
                |> (static fn ($x) => array_filter($x, static fn (string $url): bool => '' !== $url))
                |> array_values(...);
    }

    /** @return list<string> */
    private function existingSlugs(VacancyData $data): array
    {
        return explode(',', $data->techStacks->existingTags ?? '')
                |> (static fn ($x) => array_map(trim(...), $x))
                |> (static fn ($x) => array_filter($x, static fn (string $slug): bool => '' !== $slug))
                |> array_unique(...)
                |> array_values(...);
    }
}
