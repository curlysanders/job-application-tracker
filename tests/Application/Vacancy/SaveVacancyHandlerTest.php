<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\SaveVacancy;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\SaveVacancyHandler;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class SaveVacancyHandlerTest extends TestCase
{
    private const string USER_ID = '018f8e3e-1234-7abc-8def-0123456789ab';
    private const string FOREIGN_COMPANY_ID = '018f8e3e-5678-7abc-8def-0123456789ab';

    public function testRejectsACompanyThatIsNotOwnedByTheVacancyUser(): void
    {
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(self::USER_ID)->willReturn(new User());
        $companies = $this->createMock(CompanyRepository::class);
        $companies->expects(self::once())->method('findOwnedBy')->with(self::FOREIGN_COMPANY_ID, self::USER_ID)->willReturn(null);
        $vacancies = self::createStub(VacancyRepository::class);
        $recruiters = self::createStub(RecruiterRepository::class);
        $techStacks = self::createStub(TechStackRepository::class);

        $this->expectException(\LogicException::class);

        new SaveVacancyHandler(
            $users,
            $vacancies,
            $companies,
            $recruiters,
            $techStacks,
        )($this->command());
    }

    private function command(): SaveVacancy
    {
        return new SaveVacancy(
            self::USER_ID,
            null,
            'Senior PHP Developer',
            self::FOREIGN_COMPANY_ID,
            null,
            null,
            null,
            [],
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            'EUR',
            null,
            null,
            null,
            [],
            [],
        );
    }
}
