<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisRequestRepository;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisResult;
use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(AnalysisRequestRepository::class)]
final readonly class DoctrineAnalysisRequestRepository implements AnalysisRequestRepository
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function findOwnedBy(
        string $requestId,
        string $userId,
    ): ?AnalysisRequest {
        if (!Identifier::isValid($requestId) || !Identifier::isValid($userId)) {
            return null;
        }

        return $this->entityManager->getRepository(AnalysisRequest::class)->findOneBy([
            'id' => Identifier::fromString($requestId),
            'user' => Identifier::fromString($userId),
        ]);
    }

    public function find(string $requestId): ?AnalysisRequest
    {
        return Identifier::isValid($requestId)
            ? $this->entityManager->find(AnalysisRequest::class, Identifier::fromString($requestId))
            : null;
    }

    public function save(AnalysisRequest $request): void
    {
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    public function complete(
        string $requestId,
        string $inputFingerprint,
        string $resultCiphertext,
        string $resultFingerprint,
    ): bool {
        if (!Identifier::isValid($requestId)) {
            return false;
        }

        return $this->entityManager->wrapInTransaction(function () use ($requestId, $inputFingerprint, $resultCiphertext, $resultFingerprint): bool {
            $request = $this->entityManager->find(AnalysisRequest::class, Identifier::fromString($requestId), LockMode::PESSIMISTIC_WRITE);
            if (!$request instanceof AnalysisRequest || !$request->isCurrentPending($inputFingerprint)) {
                return false;
            }

            $request->complete(new AnalysisResult($request, $resultCiphertext, $resultFingerprint));
            $this->entityManager->persist($request);

            return true;
        });
    }

    public function fail(
        string $requestId,
        string $inputFingerprint,
    ): void {
        if (!Identifier::isValid($requestId)) {
            return;
        }

        $this->entityManager->wrapInTransaction(function () use ($requestId, $inputFingerprint): void {
            $request = $this->entityManager->find(AnalysisRequest::class, Identifier::fromString($requestId), LockMode::PESSIMISTIC_WRITE);
            if (!$request instanceof AnalysisRequest) {
                return;
            }
            $request->fail($inputFingerprint);
            $this->entityManager->persist($request);
        });
    }

    public function purgeExpired(\DateTimeImmutable $now): int
    {
        $deleted = $this->entityManager->createQueryBuilder()
            ->delete(AnalysisRequest::class, 'request')
            ->where('request.expiresAt <= :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
        if (!is_int($deleted)) {
            throw new \LogicException('Deleting expired analysis requests must return an affected-row count.');
        }

        return $deleted;
    }
}
