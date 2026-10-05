<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VacancyIndexesTest extends WebTestCase
{
    public function testVacancyIndexesMatchTheDashboardAndReminderQueries(): void
    {
        self::bootKernel();
        /** @var EntityManagerInterface $entityManager */
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        /** @var list<array{Key_name: string, Column_name: string, Seq_in_index: int|string}> $rows */
        $rows = $connection->fetchAllAssociative('SHOW INDEX FROM vacancies');
        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row['Key_name']][(int) $row['Seq_in_index']] = $row['Column_name'];
        }
        foreach ($indexes as &$columns) {
            ksort($columns);
            $columns = array_values($columns);
        }
        unset($columns);

        self::assertSame(['user_id', 'archived', 'date_added'], $indexes['IDX_VACANCIES_DASHBOARD'] ?? null);
        self::assertSame(['user_id', 'archived', 'status', 'date_added'], $indexes['IDX_VACANCIES_DASHBOARD_STATUS'] ?? null);
        self::assertSame(['user_id', 'archived', 'next_action_at'], $indexes['IDX_VACANCIES_REMINDERS'] ?? null);
        self::assertArrayNotHasKey('IDX_VACANCIES_STATUS', $indexes);
        self::assertArrayNotHasKey('IDX_VACANCIES_USER', $indexes);
        self::assertArrayNotHasKey('IDX_VACANCIES_DATE_ADDED', $indexes);
    }
}
