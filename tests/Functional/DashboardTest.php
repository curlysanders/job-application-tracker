<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredSalary;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\SalaryRange;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyArchived;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use Doctrine\ORM\EntityManagerInterface;
use Lingoda\DomainEventsBundle\Infra\Doctrine\Entity\OutboxRecord;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class DashboardTest extends WebTestCase
{
    public function testAnonymousUsersAreRedirectedFromDashboardPipelineFragment(): void
    {
        $client = self::createClient();
        $client->request('GET', '/pipeline/vacancies');

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
        $this->entityManager()->getConnection()->executeStatement(
            'UPDATE vacancies SET archived = 1 WHERE id = :id',
            ['id' => $archived->getId()],
            ['id' => UuidType::NAME],
        );
        $this->createVacancy($this->createUser('other-dashboard@example.com'), 'Private vacancy', VacancyStatus::Applying);

        $client->loginUser($user);
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.vacancy-chevron[href="/?status=BOOKMARKED"]', '1');
        self::assertSelectorTextContains('.vacancy-chevron[href="/?status=APPLYING"]', '0');
        self::assertSelectorTextContains('.vacancy-chevron[href="/?status=APPLIED"]', '1');
        self::assertSelectorTextContains('.vacancy-chevron[href="/?status=INTERVIEWING"]', '0');
        self::assertSelectorTextContains('.vacancy-chevron[href="/?status=NEGOTIATING"]', '0');
        self::assertSelectorTextContains('.vacancy-chevron[href="/?status=ACCEPTED"]', '1');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Bookmarked vacancy');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Applied vacancy');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Accepted vacancy');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Withdrawn vacancy');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Archived vacancy');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Private vacancy');
        self::assertSelectorCount(4, '.dashboard-vacancy-card');
        $cardText = implode(' ', $crawler->filter('.dashboard-vacancy-card')->each(static fn ($card): string => $card->text()));
        self::assertStringContainsString('Bookmarked vacancy', $cardText);
        self::assertStringContainsString('Applied vacancy', $cardText);
        self::assertSelectorExists('.dashboard-vacancy-card .dashboard-vacancy-actions summary');
        self::assertSelectorExists('[data-controller="vacancy-pipeline"]');
        self::assertSelectorExists('[data-vacancy-pipeline-target="results"]');

        $crawler = $client->request('GET', '/?status=APPLIED');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.vacancy-chevron-selected[href="/?status=APPLIED"][aria-current="true"]');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Applied vacancy');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Bookmarked vacancy');
        self::assertSelectorTextContains('.vacancy-pipeline-clear', 'Show all');

        $client->request('GET', '/pipeline/vacancies?status=APPLIED');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.dashboard-vacancy-table-panel');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Applied vacancy');
        self::assertStringNotContainsString('<!DOCTYPE html>', (string) $client->getResponse()->getContent());
    }

    public function testDashboardAcceptsTerminalStatusFiltersAndRejectsUnknownValues(): void
    {
        $client = self::createClient();
        $user = $this->createUser('invalid-dashboard@example.com');
        $client->loginUser($user);

        $client->request('GET', '/?status=I_WITHDREW');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/pipeline/vacancies?status=UNKNOWN');
        self::assertResponseStatusCodeSame(404);
    }

    public function testDashboardPaginatesTwentyVacanciesPerPage(): void
    {
        $client = self::createClient();
        $user = $this->createUser('pagination-dashboard@example.com');
        for ($number = 1; 21 >= $number; ++$number) {
            $this->createVacancy($user, sprintf('Pagination vacancy %02d', $number), VacancyStatus::Bookmarked);
        }

        $client->loginUser($user);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(20, '.dashboard-vacancy-table tbody tr');
        self::assertSelectorTextContains('.dashboard-vacancy-pagination', 'Page 1 of 2');

        $client->request('GET', '/?page=2');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, '.dashboard-vacancy-table tbody tr');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Pagination vacancy 01');
    }

    public function testDashboardFiltersBySearchRatingWorkModeAndSalaryFit(): void
    {
        $client = self::createClient();
        $user = $this->createUser('filters-dashboard@example.com');
        $user->updatePreferences(PreferredSalary::fromDecimal('4500.00', 'EUR'), null, null);
        $meetsTarget = $this->createVacancy($user, 'Remote platform engineer', VacancyStatus::Applied);
        $meetsTarget->setExcitement(4);
        $meetsTarget->replaceSalaryRange(SalaryRange::fromDecimals('4000.00', '5000.00', 'EUR'));
        $techStack = new TechStack('Symfony', 'Backend');
        $meetsTarget->replaceTechStacks($techStack);
        $this->entityManager()->persist($techStack);
        $this->entityManager()->flush();
        $belowTarget = $this->createVacancy($user, 'Office developer', VacancyStatus::Applied);
        $belowTarget->replaceSalaryRange(SalaryRange::fromDecimals('3500.00', '4000.00', 'EUR'));
        $this->entityManager()->flush();
        $this->entityManager()->getConnection()->executeStatement(
            'UPDATE vacancies SET work_mode = :mode WHERE id = :id',
            ['mode' => 'REMOTE', 'id' => $meetsTarget->getId()],
            ['id' => UuidType::NAME],
        );

        $client->loginUser($user);
        $client->request('GET', '/?q=symfony&excitement=4&work_mode=REMOTE&salary_fit=MEETS_TARGET');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Remote platform engineer');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Office developer');
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Meets target');

        $client->request('GET', '/?salary_fit=BELOW_TARGET');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.dashboard-vacancy-table', 'Office developer');
        self::assertSelectorTextNotContains('.dashboard-vacancy-table', 'Remote platform engineer');
    }

    public function testDashboardArchivesRestoresAndDeletesAnOwnedVacancy(): void
    {
        $client = self::createClient();
        $user = $this->createUser('actions-dashboard@example.com');
        $vacancy = $this->createVacancy($user, 'Action vacancy', VacancyStatus::Bookmarked);
        $client->loginUser($user);
        $crawler = $client->request('GET', '/');
        $archiveToken = $crawler->filter('form[action$="/archive"] input[name="_token"]')->attr('value');
        self::assertNotNull($archiveToken);

        $client->request('POST', sprintf('/vacancies/%s/archive', $vacancy->getId()->toRfc4122()), [
            '_token' => $archiveToken,
            'archived' => '1',
            'return' => '/',
        ]);
        self::assertResponseRedirects('/');
        $this->entityManager()->clear();
        $archivedVacancy = $this->entityManager()->find(Vacancy::class, $vacancy->getId());
        self::assertInstanceOf(Vacancy::class, $archivedVacancy);
        self::assertTrue($archivedVacancy->isArchived());
        $outboxRecord = $this->entityManager()->getRepository(OutboxRecord::class)->findOneBy([
            'entityId' => $vacancy->getId()->toRfc4122(),
            'eventType' => VacancyArchived::class,
        ]);
        self::assertInstanceOf(OutboxRecord::class, $outboxRecord);

        $crawler = $client->request('GET', '/?archived=1');
        $restoreToken = $crawler->filter('form[action$="/archive"] input[name="_token"]')->attr('value');
        self::assertNotNull($restoreToken);
        $client->request('POST', sprintf('/vacancies/%s/archive', $vacancy->getId()->toRfc4122()), [
            '_token' => $restoreToken,
            'archived' => '0',
            'return' => '/',
        ]);
        $this->entityManager()->clear();
        $restoredVacancy = $this->entityManager()->find(Vacancy::class, $vacancy->getId());
        self::assertInstanceOf(Vacancy::class, $restoredVacancy);
        self::assertFalse($restoredVacancy->isArchived());

        $crawler = $client->request('GET', '/');
        $archiveToken = $crawler->filter('form[action$="/archive"] input[name="_token"]')->attr('value');
        self::assertNotNull($archiveToken);
        $client->request('POST', sprintf('/vacancies/%s/archive', $vacancy->getId()->toRfc4122()), [
            '_token' => $archiveToken,
            'archived' => '1',
            'return' => '/?archived=1',
        ]);

        $crawler = $client->request('GET', '/?archived=1');
        $deleteToken = $crawler->filter('form[action$="/delete"] input[name="_token"]')->attr('value');
        self::assertNotNull($deleteToken);
        $client->request('POST', sprintf('/vacancies/%s/delete', $vacancy->getId()->toRfc4122()), [
            '_token' => $deleteToken,
            'confirm' => 'delete',
            'return' => '/?archived=1',
        ]);
        self::assertResponseRedirects('/?archived=1');
        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->find(Vacancy::class, $vacancy->getId()));
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
