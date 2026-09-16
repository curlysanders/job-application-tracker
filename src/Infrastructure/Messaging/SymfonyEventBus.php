<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\EventBus;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsAlias(EventBus::class)]
final readonly class SymfonyEventBus implements EventBus
{
    public function __construct(
        #[Autowire(service: 'event.bus')]
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(object $event): void
    {
        $this->messageBus->dispatch($event);
    }
}
