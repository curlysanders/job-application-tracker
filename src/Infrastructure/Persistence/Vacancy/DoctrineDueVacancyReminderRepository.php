<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Reminder\DueVacancyReminder;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Reminder\DueVacancyReminderRepository;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\IdentifierType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(DueVacancyReminderRepository::class)]
final readonly class DoctrineDueVacancyReminderRepository implements DueVacancyReminderRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function forUserDueBefore(
        string $userId,
        \DateTimeImmutable $endExclusive,
    ): array {
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
            ->setParameter('userId', Identifier::fromString($userId), IdentifierType::NAME)
            ->setParameter('endExclusive', $endExclusive)
            ->getQuery()
            ->getResult();

        return array_map(static function (Vacancy $vacancy): DueVacancyReminder {
            $id = $vacancy->id;
            $nextActionAt = $vacancy->nextActionAt ?? throw new \LogicException('Due reminders need a date and time.');

            return new DueVacancyReminder($id->toRfc4122(), $vacancy->title, $vacancy->nextActionTitle, $nextActionAt);
        }, $vacancies);
    }
}
