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
use CurlySanders\JobApplicationTracker\Domain\User\User;
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
        $creating = null === $command->vacancyId;
        $vacancy = $creating
            ? new Vacancy($user, $command->title)
            : $this->vacancies->findOwnedBy($command->vacancyId, $command->userId) ?? throw new \LogicException('The vacancy no longer exists.');
        $vacancy->updateTitle($command->title);
        $vacancy->updateAuthoringDetails(
            $this->company($command->companyId, $command->createCompanyName, $command->userId, $user),
            $this->recruiter($command->recruiterId, $command->createRecruiterAgencyName, $command->userId, $user),
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
        if ($creating) {
            $vacancy->recordCreated();
        } else {
            $vacancy->recordDetailsUpdated();
        }
        $this->vacancies->save($vacancy);

        return $vacancy;
    }

    private function company(
        ?string $id,
        ?string $createName,
        string $userId,
        User $user,
    ): ?Company {
        if (null !== $id) {
            return $this->companies->findOwnedBy($id, $userId) ?? throw new \LogicException('The selected company no longer exists.');
        }
        if (null === $createName) {
            return null;
        }

        $company = new Company($user, $createName, null, null);
        $company->recordCreated();
        $this->companies->save($company);

        return $company;
    }

    private function recruiter(
        ?string $id,
        ?string $createName,
        string $userId,
        User $user,
    ): ?Recruiter {
        if (null !== $id) {
            return $this->recruiters->findOwnedBy($id, $userId) ?? throw new \LogicException('The selected recruiter no longer exists.');
        }
        if (null === $createName) {
            return null;
        }

        $recruiter = new Recruiter($user, $createName, null);
        $recruiter->recordCreated();
        $this->recruiters->save($recruiter);

        return $recruiter;
    }

    /** @return list<TechStack> */
    private function resolveTechStacks(SaveVacancy $command): array
    {
        $resolved = [];
        foreach ($command->existingTechStackSlugs as $slug) {
            $techStack = $this->techStacks->findBySlug($slug) ?? throw new \LogicException('A selected technology no longer exists.');
            $resolved[$techStack->slug] = $techStack;
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
