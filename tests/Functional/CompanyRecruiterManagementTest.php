<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CompanyRecruiterManagementTest extends WebTestCase
{
    public function testAnonymousUsersCannotManageCompaniesOrRecruiters(): void
    {
        $client = self::createClient();
        $client->request('GET', '/app/companies');
        self::assertResponseRedirects('/login');
        $client->request('GET', '/app/recruiters');
        self::assertResponseRedirects('/login');
    }

    public function testUserCanCreateEditAndSearchACompanyWithMultipleContacts(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createUser('company@example.com'));
        $crawler = $client->request('GET', '/app/companies/new');
        $client->request('POST', '/app/companies/new', ['company' => [
            '_token' => $this->csrfToken($crawler), 'name' => 'Acme BV', 'website' => 'https://acme.example', 'industry' => 'Software',
            'directContacts' => [
                ['name' => 'Ada Lovelace', 'email' => 'ada@acme.example', 'phone' => '+31 6 12345678', 'linkedinUrl' => 'https://www.linkedin.com/in/ada'],
                ['name' => 'Grace Hopper', 'email' => 'grace@acme.example', 'phone' => null, 'linkedinUrl' => null],
            ],
        ]]);
        self::assertResponseRedirects('/app/companies');
        $this->entityManager()->clear();
        $company = $this->entityManager()->getRepository(Company::class)->findOneBy(['name' => 'Acme BV']);
        self::assertInstanceOf(Company::class, $company);
        self::assertCount(2, $company->getDirectContacts());

        $crawler = $client->request('GET', sprintf('/app/companies/%d/edit', $company->getId()));
        self::assertSelectorCount(1, 'input[name="company[directContacts][0][name]"]');
        $client->request('POST', sprintf('/app/companies/%d/edit', $company->getId()), ['company' => [
            '_token' => $this->csrfToken($crawler), 'name' => 'Acme Europe BV', 'website' => 'https://acme.example', 'industry' => 'Software',
            'directContacts' => [['name' => 'Ada Lovelace', 'email' => 'ada@acme.example', 'phone' => '+31 6 12345678', 'linkedinUrl' => 'https://www.linkedin.com/in/ada']],
        ]]);
        self::assertResponseRedirects('/app/companies');
        $client->request('GET', '/app/companies?q=Ada');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.management-results', 'Acme Europe BV');
    }

    public function testUserCanCreateAndSearchRecruitersAndSeesValidationErrors(): void
    {
        $client = self::createClient();
        $client->loginUser($this->createUser('recruiter@example.com'));
        $crawler = $client->request('GET', '/app/recruiters/new');
        $client->request('POST', '/app/recruiters/new', ['recruiter' => ['_token' => $this->csrfToken($crawler), 'agencyName' => '', 'website' => 'not a url']]);
        self::assertResponseStatusCodeSame(422);

        $crawler = $client->request('GET', '/app/recruiters/new');
        $client->request('POST', '/app/recruiters/new', ['recruiter' => [
            '_token' => $this->csrfToken($crawler), 'agencyName' => 'Talent Partners', 'website' => 'https://talent.example',
            'directContacts' => [['name' => 'Lin Recruiter', 'email' => 'lin@talent.example', 'phone' => null, 'linkedinUrl' => null]],
        ]]);
        self::assertResponseRedirects('/app/recruiters');
        $this->entityManager()->clear();
        $recruiter = $this->entityManager()->getRepository(Recruiter::class)->findOneBy(['agencyName' => 'Talent Partners']);
        self::assertInstanceOf(Recruiter::class, $recruiter);
        self::assertCount(1, $recruiter->getDirectContacts());
        $client->request('GET', '/app/recruiters?q=lin@talent.example');
        self::assertSelectorTextContains('.management-results', 'Talent Partners');
    }

    private function csrfToken(\Symfony\Component\DomCrawler\Crawler $crawler): string
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
