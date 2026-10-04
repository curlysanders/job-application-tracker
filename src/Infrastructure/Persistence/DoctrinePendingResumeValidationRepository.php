<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence;

use CurlySanders\JobApplicationTracker\Application\UserProfile\PendingResumeValidationRepository;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeValidationOutcome;
use CurlySanders\JobApplicationTracker\Domain\User\PendingResume;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Uid\Uuid;

#[AsAlias(PendingResumeValidationRepository::class)]
final readonly class DoctrinePendingResumeValidationRepository implements PendingResumeValidationRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findPendingResume(string $userId, string $validationId): ?PendingResume
    {
        if (!Uuid::isValid($userId) || !Uuid::isValid($validationId)) {
            return null;
        }

        $user = $this->entityManager->find(User::class, Uuid::fromString($userId));
        $pending = $user instanceof User ? $user->getPendingResume() : null;

        return null !== $pending && $pending->validationId->equals(Uuid::fromString($validationId)) ? $pending : null;
    }

    public function completePendingResumeValidation(string $userId, string $validationId, ResumeValidationOutcome $outcome): ?string
    {
        if (!Uuid::isValid($userId) || !Uuid::isValid($validationId)) {
            return null;
        }

        $userId = Uuid::fromString($userId);
        $validationId = Uuid::fromString($validationId);

        return $this->entityManager->wrapInTransaction(function () use ($userId, $validationId, $outcome): ?string {
            $user = $this->entityManager->find(User::class, $userId, LockMode::PESSIMISTIC_WRITE);
            if (!$user instanceof User) {
                return null;
            }

            return $outcome->valid
                ? $user->promotePendingResume($validationId)
                : $user->rejectPendingResume($validationId, $outcome->failure ?? throw new \LogicException('An invalid resume outcome requires a failure message.'));
        });
    }
}
