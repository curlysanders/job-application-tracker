<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredSalary;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ApplicationSource;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class VacancyAuthoringTest extends WebTestCase
{
    public function testAnonymousUsersCannotAuthorVacancies(): void
    {
        $client = self::createClient();
        $client->request('GET', '/app/vacancies/new');

        self::assertResponseRedirects('/login');
    }

    public function testUserCanCreateAndEditACompleteVacancyWithCustomTechnology(): void
    {
        $client = self::createClient();
        $user = $this->createUser('vacancy@example.com');
        $user->updatePreferences(PreferredSalary::fromDecimal('5000.00', 'EUR'), null, null);
        $company = new Company('Acme BV', null, null);
        $recruiter = new Recruiter('Talent Partners', null);
        $php = new TechStack('PHP', 'Backend');
        $this->entityManager()->persist($company);
        $this->entityManager()->persist($recruiter);
        $this->entityManager()->persist($php);
        $this->entityManager()->flush();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/app/vacancies/new');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-vacancy-authoring-minimum-preferred-salary-value="5000.00"]');
        self::assertSelectorExists('select[name="vacancy[currencyCode]"] option[value="EUR"][selected]');
        self::assertSelectorExists('select[name="vacancy[currencyCode]"] option[value="USD"]');
        self::assertSelectorNotExists('select[name="vacancy[currencyCode]"] option[value="DEM"]');
        $client->request('POST', '/app/vacancies/new', ['vacancy' => $this->formData($crawler, $company, $recruiter, $php, 'Senior PHP Developer')]);
        self::assertResponseRedirects();

        $this->entityManager()->clear();
        $vacancy = $this->entityManager()->getRepository(Vacancy::class)->findOneBy(['title' => 'Senior PHP Developer']);
        self::assertInstanceOf(Vacancy::class, $vacancy);
        self::assertSame('Rotterdam', $vacancy->getLocation());
        self::assertSame(ApplicationSource::LinkedIn, $vacancy->getApplicationSource());
        self::assertSame(['https://jobs.example/vacancy'], $vacancy->getSourceUrls());
        self::assertSame('Build reliable APIs.', $vacancy->getRequirements());
        $salaryRange = $vacancy->getSalaryRange();
        self::assertNotNull($salaryRange);
        self::assertSame('4500.00', $salaryRange->getMinimum()?->getAmount()->toString());
        self::assertSame('5500.00', $salaryRange->getMaximum()?->getAmount()->toString());
        self::assertSame(WorkMode::Hybrid, $vacancy->getWorkMode());
        self::assertSame(['php', 'symfony'], $vacancy->getTechStacks()->map(static fn (TechStack $tag): string => $tag->getSlug())->toArray());
        self::assertInstanceOf(TechStack::class, $this->entityManager()->getRepository(TechStack::class)->findOneBy(['slug' => 'symfony']));

        $crawler = $client->request('GET', sprintf('/app/vacancies/%d/edit', $vacancy->getId()));
        self::assertSame('Senior PHP Developer', $crawler->filter('input[name="vacancy[title]"]')->attr('value'));
        $editData = $this->formData($crawler, $company, $recruiter, $php, 'Lead PHP Developer');
        self::assertIsArray($editData['techStacks']);
        $editData['techStacks']['newTags'] = [['name' => 'Symfony', 'category' => 'Framework']];
        $client->request('POST', sprintf('/app/vacancies/%d/edit', $vacancy->getId()), ['vacancy' => $editData]);
        self::assertResponseRedirects(sprintf('/app/vacancies/%d/edit', $vacancy->getId()));

        $this->entityManager()->clear();
        $updated = $this->entityManager()->find(Vacancy::class, $vacancy->getId());
        self::assertInstanceOf(Vacancy::class, $updated);
        self::assertSame('Lead PHP Developer', $updated->getTitle());
        self::assertCount(2, $updated->getTechStacks());
    }

    public function testInvalidSalaryRangeAndAnotherUsersVacancyAreRejected(): void
    {
        $client = self::createClient();
        $user = $this->createUser('vacancy-validation@example.com');
        $client->loginUser($user);
        $crawler = $client->request('GET', '/app/vacancies/new');
        self::assertResponseIsSuccessful();
        $data = $this->formData($crawler, null, null, null, 'Invalid salary');
        $data['minimumSalary'] = '6000.00';
        $data['maximumSalary'] = '5000.00';
        $client->request('POST', '/app/vacancies/new', ['vacancy' => $data]);
        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('.vacancy-form-panel', 'The minimum salary cannot exceed the maximum salary.');

        $otherUser = $this->createUser('other-vacancy@example.com');
        $vacancy = new Vacancy($otherUser, 'Private vacancy');
        $this->entityManager()->persist($vacancy);
        $this->entityManager()->flush();
        $client->request('GET', sprintf('/app/vacancies/%d/edit', $vacancy->getId()));
        self::assertResponseStatusCodeSame(404);
    }

    /** @return array<string, mixed> */
    private function formData(Crawler $crawler, ?Company $company, ?Recruiter $recruiter, ?TechStack $php, string $title): array
    {
        return [
            '_token' => $this->csrfToken($crawler), 'title' => $title, 'companyId' => $company?->getId(), 'recruiterId' => $recruiter?->getId(), 'location' => 'Rotterdam',
            'applicationSource' => ApplicationSource::LinkedIn->value, 'sourceUrls' => ['https://jobs.example/vacancy'], 'howToApply' => 'Apply via the company site.',
            'contractType' => 'PERMANENT', 'datePosted' => '2026-09-20', 'deadline' => '2026-10-01', 'dateApplied' => '2026-09-22',
            'fullText' => 'Full vacancy text.', 'requirements' => 'Build reliable APIs.', 'responsibilities' => 'Lead delivery.', 'preferredQualifications' => 'Symfony expertise.',
            'aboutJob' => 'Build products.', 'aboutCompany' => 'A product company.', 'compensationBenefits' => 'Pension.',
            'minimumSalary' => '4500.00', 'maximumSalary' => '5500.00', 'currencyCode' => 'EUR', 'workMode' => WorkMode::Hybrid->value, 'hybridDetails' => 'Two days in Rotterdam.',
            'techStacks' => ['existingTags' => $php?->getSlug() ?? '', 'newTags' => [['name' => 'Symfony', 'category' => 'Framework']]], 'excitement' => 4,
        ];
    }

    private function csrfToken(Crawler $crawler): string
    {
        $token = $crawler->filter('input[name$="[_token]"]')->attr('value');
        self::assertIsString($token);

        return $token;
    }

    private function createUser(string $email): User
    {
        $user = new User()->setEmail($email);
        $user->setPassword($this->passwordHasher()->hashPassword($user, 'SecurePassword1!'));
        $this->entityManager()->persist($user);
        $this->entityManager()->flush();

        return $user;
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }

    private function passwordHasher(): UserPasswordHasherInterface
    {
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertInstanceOf(UserPasswordHasherInterface::class, $hasher);

        return $hasher;
    }
}
