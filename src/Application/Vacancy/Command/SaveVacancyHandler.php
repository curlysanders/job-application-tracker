<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Command;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\SalaryRange;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;

final readonly class SaveVacancyHandler implements CommandHandler
{
    public function __construct(
        private UserRepository $users,
        private VacancyRepository $vacancies,
        private CompanyRepository $companies,
        private RecruiterRepository $recruiters,
        private TechStackRepository $techStacks,
    ) {
    }

    public function __invoke(SaveVacancy $command): Vacancy
    {
        $user = $this->users->find($command->userId) ?? throw new \LogicException('The authenticated user no longer exists.');
        $vacancy = null === $command->vacancyId
            ? new Vacancy($user, $command->title)
            : $this->vacancies->findOwnedBy($command->vacancyId, $command->userId) ?? throw new \LogicException('The vacancy no longer exists.');
        $vacancy->updateTitle($command->title);
        $vacancy->updateAuthoringDetails(
            $this->optionalCompany($command->companyId),
            $this->optionalRecruiter($command->recruiterId),
            $command->fullText,
            $command->requirements,
            $command->responsibilities,
            $command->preferredQualifications,
            $command->aboutJob,
            $command->aboutCompany,
            $command->compensationBenefits,
            $command->sourceUrls,
            $command->howToApply,
            $command->location,
            $command->workMode,
            $command->hybridDetails,
            $command->datePosted,
            $command->deadline,
            $command->dateApplied,
            $command->contractType,
            $command->applicationSource,
        );
        $vacancy->replaceSalaryRange(SalaryRange::fromDecimals($command->minimumSalary, $command->maximumSalary, $command->currencyCode));
        $vacancy->setExcitement($command->excitement);
        $vacancy->replaceTechStacks(...$this->resolveTechStacks($command));
        $this->vacancies->save($vacancy);

        return $vacancy;
    }

    private function optionalCompany(?int $id): ?Company
    {
        return null === $id ? null : ($this->companies->find($id) ?? throw new \LogicException('The selected company no longer exists.'));
    }

    private function optionalRecruiter(?int $id): ?Recruiter
    {
        return null === $id ? null : ($this->recruiters->find($id) ?? throw new \LogicException('The selected recruiter no longer exists.'));
    }

    /** @return list<TechStack> */
    private function resolveTechStacks(SaveVacancy $command): array
    {
        $resolved = [];
        foreach ($command->existingTechStackSlugs as $slug) {
            $techStack = $this->techStacks->findBySlug($slug) ?? throw new \LogicException('A selected technology no longer exists.');
            $resolved[$techStack->getSlug()] = $techStack;
        }
        foreach ($command->newTechStacks as $newTechStack) {
            $slug = TechStack::slug($newTechStack['name']);
            $techStack = $this->techStacks->findBySlug($slug);
            if (null === $techStack) {
                $techStack = new TechStack($newTechStack['name'], $newTechStack['category']);
                $this->techStacks->save($techStack);
            }
            $resolved[$slug] = $techStack;
        }

        return array_values($resolved);
    }
}
