<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Application\Authentication\Command\RegisterUser;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\User\UserRegistered;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\LingodaDomainEvent;
use Doctrine\ORM\EntityManagerInterface;
use Lingoda\DomainEventsBundle\Infra\Doctrine\Entity\OutboxRecord;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AuthenticationBusTest extends KernelTestCase
{
    public function testRegisterUserCommandPersistsAUserAndRecordsAnOutboxEvent(): void
    {
        self::bootKernel();

        $commandBus = self::getContainer()->get(CommandBus::class);
        self::assertInstanceOf(CommandBus::class, $commandBus);
        $user = $commandBus->dispatch(new RegisterUser('  Sander@example.com ', 'SecurePassword1!'));

        self::assertInstanceOf(User::class, $user);
        self::assertSame('sander@example.com', $user->email);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->clear();
        $records = $entityManager->getRepository(OutboxRecord::class)->findBy(['entityId' => $user->id->toRfc4122()]);
        $matchingRecords = array_values(array_filter(
            $records,
            static fn (OutboxRecord $record): bool => $record->getDomainEvent() instanceof LingodaDomainEvent
                && $record->getDomainEvent()->event() instanceof UserRegistered,
        ));
        self::assertCount(1, $matchingRecords);
        $outboxRecord = $matchingRecords[0];
        $event = $outboxRecord->getDomainEvent();
        self::assertInstanceOf(LingodaDomainEvent::class, $event);
        $event = $event->event();
        self::assertInstanceOf(UserRegistered::class, $event);
        self::assertSame([], $event->changedProperties);
    }
}
