<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Application\Authentication\Command\RegisterUser;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\User\UserRegistered;
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
        self::assertSame('sander@example.com', $user->getEmail());

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $outboxRecord = $entityManager->getRepository(OutboxRecord::class)->findOneBy([
            'entityId' => $user->getId()->toRfc4122(),
            'eventType' => UserRegistered::class,
        ]);
        self::assertInstanceOf(OutboxRecord::class, $outboxRecord);
        $event = $outboxRecord->getDomainEvent();
        self::assertInstanceOf(UserRegistered::class, $event);
        self::assertSame([], $event->changedProperties);
    }
}
