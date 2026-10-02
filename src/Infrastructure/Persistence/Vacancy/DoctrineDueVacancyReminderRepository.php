<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Reminder\DueVacancyReminder;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Reminder\DueVacancyReminderRepository;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(DueVacancyReminderRepository::class)]
final readonly class DoctrineDueVacancyReminderRepository implements DueVacancyReminderRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function forUserDueBefore(string $userId, \DateTimeImmutable $endExclusive): array
    {
        /** @var list<Vacancy> $vacancies */
        $vacancies = $this->entityManager->createQueryBuilder()
            ->select('vacancy')
            ->from(Vacancy::class, 'vacancy')
            ->where('IDENTITY(vacancy.user) = :userId')
            ->andWhere('vacancy.archived = false')
            ->andWhere('vacancy.nextActionAt IS NOT NULL')
            ->andWhere('vacancy.nextActionAt < :endExclusive')
            ->orderBy('vacancy.nextActionAt', 'ASC')
            ->addOrderBy('vacancy.id', 'ASC')
            ->setParameter('userId', Uuid::fromString($userId), UuidType::NAME)
            ->setParameter('endExclusive', $endExclusive)
            ->getQuery()
            ->getResult();

        return array_map(static function (Vacancy $vacancy): DueVacancyReminder {
            $id = $vacancy->getId();
            $nextActionAt = $vacancy->getNextActionAt() ?? throw new \LogicException('Due reminders need a date and time.');

            return new DueVacancyReminder($id->toRfc4122(), $vacancy->getTitle(), $vacancy->getNextActionTitle(), $nextActionAt);
        }, $vacancies);
    }
}
