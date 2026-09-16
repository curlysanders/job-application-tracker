<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Functional;

use CurlySanders\JobApplicationTracker\Application\Authentication\Command\RegisterUser;
use CurlySanders\JobApplicationTracker\Application\Authentication\Event\UserRegistered;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\TraceableMessageBus;

final class AuthenticationBusTest extends KernelTestCase
{
    public function testRegisterUserCommandPersistsAUserAndPublishesAnEvent(): void
    {
        self::bootKernel();

        $eventBus = self::getContainer()->get('event.bus');
        self::assertInstanceOf(TraceableMessageBus::class, $eventBus);
        $eventBus->reset();

        $commandBus = self::getContainer()->get(CommandBus::class);
        self::assertInstanceOf(CommandBus::class, $commandBus);
        $user = $commandBus->dispatch(new RegisterUser('  Sander@example.com ', 'SecurePassword1!'));

        self::assertInstanceOf(User::class, $user);
        self::assertNotNull($user->getId());
        self::assertSame('sander@example.com', $user->getEmail());

        $events = $eventBus->getDispatchedMessages();
        self::assertCount(1, $events);
        self::assertInstanceOf(UserRegistered::class, $events[0]['message']);
        self::assertSame($user->getId(), $events[0]['message']->userId);
        self::assertSame($user->getEmail(), $events[0]['message']->email);
    }
}
