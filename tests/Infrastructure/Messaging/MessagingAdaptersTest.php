<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Messaging;

use CurlySanders\JobApplicationTracker\Application\UserProfile\PendingResumeValidationRepository;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeFileValidator;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ValidatePendingResume;
use CurlySanders\JobApplicationTracker\Application\Vacancy\RecordVacancyStatusHistory;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusHistoryRepository;
use CurlySanders\JobApplicationTracker\Domain\User\ResumeValidationRequested;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatusTransitioned;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\OutboxRecordIdContext;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\OutboxRecordIdContextMiddleware;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\RecordVacancyStatusTransition;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\SymfonyCommandBus;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\SymfonyEventBus;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\ValidateUploadedResume;
use Lingoda\DomainEventsBundle\Infra\Symfony\Messenger\Transport\OutboxRecordIdStamp;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Uid\Uuid;

final class MessagingAdaptersTest extends TestCase
{
    public function testOutboxContextMiddlewareScopesTheRecordIdAndRestoresItAfterFailures(): void
    {
        $context = new OutboxRecordIdContext();
        $context->replace(7);
        $middleware = new OutboxRecordIdContextMiddleware($context);
        $stack = $this->createMock(StackInterface::class);
        $next = $this->createMock(MiddlewareInterface::class);
        $stack->expects(self::once())->method('next')->willReturn($next);
        $next->expects(self::once())->method('handle')->willThrowException(new \RuntimeException('worker stopped'));

        try {
            $middleware->handle(new Envelope(new \stdClass(), [new OutboxRecordIdStamp(42)]), $stack);
            self::fail('Expected the next middleware exception.');
        } catch (\RuntimeException $exception) {
            self::assertSame('worker stopped', $exception->getMessage());
        }

        self::assertSame(7, $context->id());
    }

    public function testOutboxContextMiddlewarePassesTheOutboxIdToTheNextHandler(): void
    {
        $context = new OutboxRecordIdContext();
        $middleware = new OutboxRecordIdContextMiddleware($context);
        $stack = $this->createMock(StackInterface::class);
        $next = $this->createMock(MiddlewareInterface::class);
        $stack->expects(self::once())->method('next')->willReturn($next);
        $next->expects(self::once())->method('handle')->willReturnCallback(static function (Envelope $envelope, StackInterface $stack) use ($context): Envelope {
            self::assertSame(42, $context->id());

            return $envelope;
        });

        $envelope = new Envelope(new \stdClass(), [new OutboxRecordIdStamp(42)]);
        self::assertSame($envelope, $middleware->handle($envelope, $stack));
        self::assertNull($context->id());
    }

    public function testStatusTransitionHandlerDelegatesValidatedOutboxEvents(): void
    {
        $user = new User();
        $vacancy = new Vacancy($user, 'Backend engineer');
        $event = new VacancyStatusTransitioned($vacancy->getId(), [
            'fromStatus' => VacancyStatus::Bookmarked->value,
            'toStatus' => VacancyStatus::Applying->value,
            'note' => 'Interview arranged',
        ]);
        $vacancies = $this->createMock(VacancyRepository::class);
        $vacancies->expects(self::once())->method('find')->with($event->getEntityId())->willReturn($vacancy);
        $history = $this->createMock(VacancyStatusHistoryRepository::class);
        $history->expects(self::once())->method('findByOutboxRecordId')->with(42)->willReturn(null);
        $history->expects(self::once())->method('save');
        $context = new OutboxRecordIdContext();
        $context->replace(42);

        new RecordVacancyStatusTransition(new RecordVacancyStatusHistory($vacancies, $history), $context)($event);
    }

    public function testStatusTransitionHandlerRequiresOutboxMetadata(): void
    {
        $user = new User();
        $vacancy = new Vacancy($user, 'Backend engineer');
        $service = new RecordVacancyStatusHistory(self::createStub(VacancyRepository::class), self::createStub(VacancyStatusHistoryRepository::class));
        $handler = new RecordVacancyStatusTransition($service, new OutboxRecordIdContext());

        $this->expectException(\LogicException::class);
        $handler(new VacancyStatusTransitioned($vacancy->getId(), ['fromStatus' => VacancyStatus::Bookmarked->value, 'toStatus' => VacancyStatus::Applying->value]));
    }

    public function testStatusTransitionHandlerRejectsMalformedHistoryValues(): void
    {
        $vacancy = new Vacancy(new User(), 'Backend engineer');
        $context = new OutboxRecordIdContext();
        $context->replace(42);
        $handler = new RecordVacancyStatusTransition(
            new RecordVacancyStatusHistory(self::createStub(VacancyRepository::class), self::createStub(VacancyStatusHistoryRepository::class)),
            $context,
        );

        $this->expectException(\LogicException::class);
        $handler(new VacancyStatusTransitioned($vacancy->getId(), ['fromStatus' => VacancyStatus::Bookmarked->value, 'toStatus' => 123]));
    }

    public function testResumeValidationHandlerDelegatesAValidRequestAndRejectsInvalidMetadata(): void
    {
        $userId = Uuid::v7();
        $validationId = Uuid::v7();
        $repository = $this->createMock(PendingResumeValidationRepository::class);
        $repository->expects(self::once())->method('findPendingResume')->with($userId->toRfc4122(), $validationId->toRfc4122())->willReturn(null);
        $service = new ValidatePendingResume($repository, self::createStub(ResumeUploaderService::class), self::createStub(ResumeFileValidator::class), new NullLogger());
        $handler = new ValidateUploadedResume($service);
        $handler(new ResumeValidationRequested($userId, ['validationId' => $validationId->toRfc4122()]));

        $this->expectException(\LogicException::class);
        $handler(new ResumeValidationRequested($userId, ['validationId' => 'invalid']));
    }

    public function testSymfonyBusesReturnHandledResultsAndUnwrapFailures(): void
    {
        $command = new \stdClass();
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::once())->method('dispatch')->with($command)->willReturn(new Envelope($command, [new HandledStamp('result', 'handler')]));
        self::assertSame('result', new SymfonyCommandBus($bus)->dispatch($command));

        $eventBus = $this->createMock(MessageBusInterface::class);
        $event = new \stdClass();
        $eventBus->expects(self::once())->method('dispatch')->with($event)->willReturn(new Envelope($event));
        new SymfonyEventBus($eventBus)->dispatch($event);

        $failureBus = self::createStub(MessageBusInterface::class);
        $failure = new \DomainException('Invalid command');
        $failureBus->method('dispatch')->willThrowException(new HandlerFailedException(new Envelope($command), ['handler' => $failure]));
        $this->expectExceptionObject($failure);
        new SymfonyCommandBus($failureBus)->dispatch($command);
    }
}
