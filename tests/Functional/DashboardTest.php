<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardTest extends WebTestCase
{
    public function testAnonymousUsersAreRedirectedFromDashboardPipelineFragment(): void
    {
        $client = self::createClient();
        $client->request('GET', '/app/pipeline/vacancies');

        self::assertResponseRedirects('/login');
    }

    public function testDashboardCountsAndFiltersOnlyTheUsersActivePipelineVacancies(): void
    {
        $client = self::createClient();
        $user = $this->createUser('dashboard@example.com');
        $this->createVacancy($user, 'Bookmarked vacancy', VacancyStatus::Bookmarked);
        $this->createVacancy($user, 'Applied vacancy', VacancyStatus::Applied);
        $this->createVacancy($user, 'Accepted vacancy', VacancyStatus::Accepted);
        $this->createVacancy($user, 'Withdrawn vacancy', VacancyStatus::IWithdrew);
        $archived = $this->createVacancy($user, 'Archived vacancy', VacancyStatus::Applied);
        $this->entityManager()->getConnection()->executeStatement('UPDATE vacancies SET archived = 1 WHERE id = :id', ['id' => $archived->getId()]);
        $this->createVacancy($this->createUser('other-dashboard@example.com'), 'Private vacancy', VacancyStatus::Applying);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/app');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.vacancy-chevron[href="/app?status=BOOKMARKED"]', '1');
        self::assertSelectorTextContains('.vacancy-chevron[href="/app?status=APPLYING"]', '0');
        self::assertSelectorTextContains('.vacancy-chevron[href="/app?status=APPLIED"]', '1');
        self::assertSelectorTextContains('.vacancy-chevron[href="/app?status=INTERVIEWING"]', '0');
        self::assertSelectorTextContains('.vacancy-chevron[href="/app?status=NEGOTIATING"]', '0');
        self::assertSelectorTextContains('.vacancy-chevron[href="/app?status=ACCEPTED"]', '1');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Bookmarked vacancy');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Applied vacancy');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Accepted vacancy');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Withdrawn vacancy');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Archived vacancy');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Private vacancy');
        self::assertSelectorExists('[data-controller="vacancy-pipeline"]');
        self::assertSelectorExists('[data-vacancy-pipeline-target="results"]');

        $crawler = $client->request('GET', '/app?status=APPLIED');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.vacancy-chevron-selected[href="/app?status=APPLIED"][aria-current="true"]');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Applied vacancy');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Bookmarked vacancy');
        self::assertSelectorTextContains('.vacancy-pipeline-clear', 'Show all');

        $client->request('GET', '/app/pipeline/vacancies?status=APPLIED');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.dashboard-vacancy-table-panel');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Applied vacancy');
        self::assertStringNotContainsString('<!DOCTYPE html>', (string) $client->getResponse()->getContent());
    }

    public function testDashboardRejectsTerminalAndUnknownStatusFilters(): void
    {
        $client = self::createClient();
        $user = $this->createUser('invalid-dashboard@example.com');
        $client->loginUser($user);

        $client->request('GET', '/app?status=I_WITHDREW');
        self::assertResponseStatusCodeSame(404);

        $client->request('GET', '/app/pipeline/vacancies?status=UNKNOWN');
        self::assertResponseStatusCodeSame(404);
    }

    private function createVacancy(User $user, string $title, VacancyStatus $status): Vacancy
    {
        $vacancy = new Vacancy($user, $title);
        $vacancy->setStatus($status);
        $this->entityManager()->persist($vacancy);
        $this->entityManager()->flush();

        return $vacancy;
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
