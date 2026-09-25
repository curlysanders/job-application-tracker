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
        $data->title = $vacancy->getTitle();
        $data->companyId = $vacancy->getCompany()?->getId();
        $data->recruiterId = $vacancy->getRecruiter()?->getId();
        $data->location = $vacancy->getLocation();
        $data->applicationSource = $vacancy->getApplicationSource();
        $data->sourceUrls = $vacancy->getSourceUrls();
        $data->howToApply = $vacancy->getHowToApply();
        $data->contractType = $vacancy->getContractType();
        $data->datePosted = $vacancy->getDatePosted();
        $data->deadline = $vacancy->getDeadline();
        $data->dateApplied = $vacancy->getDateApplied();
        $data->fullText = $vacancy->getFullText();
        $data->requirements = $vacancy->getRequirements();
        $data->responsibilities = $vacancy->getResponsibilities();
        $data->preferredQualifications = $vacancy->getPreferredQualifications();
        $data->aboutJob = $vacancy->getAboutJob();
        $data->aboutCompany = $vacancy->getAboutCompany();
        $data->compensationBenefits = $vacancy->getCompensationBenefits();
        $data->minimumSalary = $vacancy->getSalaryRange()?->getMinimum()?->getAmount()->toString();
        $data->maximumSalary = $vacancy->getSalaryRange()?->getMaximum()?->getAmount()->toString();
        $data->currencyCode = $vacancy->getSalaryRange()?->getCurrencyCode() ?? 'EUR';
        $data->workMode = $vacancy->getWorkMode();
        $data->hybridDetails = $vacancy->getHybridDetails();
        $data->excitement = $vacancy->getExcitement();
        $data->techStacks->existingTags = implode(',', $vacancy->getTechStacks()->map(static fn ($techStack): string => $techStack->getSlug())->toArray());

        return $data;
    }

    public function command(int $userId, ?int $vacancyId, VacancyData $data): SaveVacancy
    {
        $newTechStacks = [];
        foreach ($data->techStacks->newTags as $newTechStack) {
            if (null === $newTechStack->name || null === $newTechStack->category) {
                throw new \LogicException('A valid new technology requires a name and category.');
            }
            $newTechStacks[] = ['name' => $newTechStack->name, 'category' => $newTechStack->category];
        }

        return new SaveVacancy(
            $userId, $vacancyId, $data->title ?? '', $data->companyId, $data->recruiterId, $data->location,
            $data->applicationSource, $this->sourceUrls($data), $data->howToApply, $data->contractType,
            $data->datePosted, $data->deadline, $data->dateApplied, $data->fullText, $data->requirements,
            $data->responsibilities, $data->preferredQualifications, $data->aboutJob, $data->aboutCompany,
            $data->compensationBenefits, $data->minimumSalary, $data->maximumSalary, strtoupper(trim($data->currencyCode)),
            $data->workMode, $data->hybridDetails, $data->excitement, $this->existingSlugs($data), $newTechStacks,
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
                |> (static fn ($x) => array_map('trim', $x))
                |> (static fn ($x) => array_filter($x, static fn (string $slug): bool => '' !== $slug))
                |> array_unique(...)
                |> array_values(...);
    }
}
