<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class VacancyReminderDashboardTest extends WebTestCase
{
    public function testDashboardShowsOnlyOwnedUnarchivedRemindersDueTodayOrEarlier(): void
    {
        $client = self::createClient();
        $user = $this->createUser('dashboard-reminders@example.com');
        $overdue = $this->vacancy($user, 'Overdue vacancy', 'Follow up', new \DateTimeImmutable('yesterday 10:00'));
        $today = $this->vacancy($user, 'Today vacancy', null, new \DateTimeImmutable('today 20:00'));
        $future = $this->vacancy($user, 'Future vacancy', 'Later', new \DateTimeImmutable('tomorrow 09:00'));
        $archived = $this->vacancy($user, 'Archived vacancy', 'Ignore', new \DateTimeImmutable('yesterday 09:00'));
        $this->entityManager()->getConnection()->executeStatement(
            'UPDATE vacancies SET archived = 1 WHERE id = :id',
            ['id' => $archived->getId()],
            ['id' => UuidType::NAME],
        );
        $this->vacancy($this->createUser('other-reminders@example.com'), 'Private vacancy', 'Private', new \DateTimeImmutable('yesterday 09:00'));
        $this->entityManager()->clear();
        $client->loginUser($user);

        $client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.dashboard-reminders', 'Overdue vacancy');
        self::assertSelectorTextContains('.dashboard-reminders', 'Today vacancy');
        self::assertSelectorTextNotContains('.dashboard-reminders', 'Future vacancy');
        self::assertSelectorTextNotContains('.dashboard-reminders', 'Archived vacancy');
        self::assertSelectorTextNotContains('.dashboard-reminders', 'Private vacancy');
        self::assertSelectorExists(sprintf('.dashboard-reminders a[href="/vacancies/%s"]', $overdue->getId()->toRfc4122()));
        self::assertSelectorExists(sprintf('.dashboard-reminders a[href="/vacancies/%s"]', $today->getId()->toRfc4122()));
    }

    private function vacancy(User $user, string $title, ?string $nextActionTitle, \DateTimeImmutable $nextActionAt): Vacancy
    {
        $vacancy = new Vacancy($user, $title);
        $this->entityManager()->persist($vacancy);
        $this->entityManager()->flush();
        $this->entityManager()->getConnection()->executeStatement(
            'UPDATE vacancies SET next_action_title = :title, next_action_at = :at WHERE id = :id',
            ['title' => $nextActionTitle, 'at' => $nextActionAt, 'id' => $vacancy->getId()],
            ['at' => Types::DATETIME_IMMUTABLE, 'id' => UuidType::NAME],
        );

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
