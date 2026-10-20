<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Application\Ai\Command\QueueAnalysisRequest;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequested;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\LingodaDomainEvent;
use Doctrine\ORM\EntityManagerInterface;
use Lingoda\DomainEventsBundle\Infra\Doctrine\Entity\OutboxRecord;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AnalysisRequestBusTest extends KernelTestCase
{
    public function testQueuesAnEncryptedAnalysisRequestWithARedactedOutboxEvent(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $user = new User()->setEmail('analysis-bus@example.com')->setPassword('hashed-password');
        $entityManager->persist($user);
        $entityManager->flush();
        $bus = self::getContainer()->get(CommandBus::class);
        self::assertInstanceOf(CommandBus::class, $bus);

        $request = $bus->dispatch(new QueueAnalysisRequest(
            $user->id->toRfc4122(),
            AnalysisKind::VacancyExtraction,
            'Private vacancy text at a full home address',
            language: 'en',
        ));
        self::assertInstanceOf(AnalysisRequest::class, $request);

        $entityManager->clear();
        $outbox = $entityManager->getRepository(OutboxRecord::class)->findOneBy([
            'entityId' => $request->id->toRfc4122(),
            'eventType' => AnalysisRequested::class,
        ]);
        self::assertInstanceOf(OutboxRecord::class, $outbox);
        $event = $outbox->getDomainEvent();
        self::assertInstanceOf(LingodaDomainEvent::class, $event);
        $event = $event->event();
        self::assertInstanceOf(AnalysisRequested::class, $event);
        self::assertSame(['inputFingerprint' => $request->inputFingerprint], $event->changedProperties);
        self::assertStringNotContainsString('Private vacancy text', json_encode($event, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('full home address', json_encode($event, JSON_THROW_ON_ERROR));
    }
}
