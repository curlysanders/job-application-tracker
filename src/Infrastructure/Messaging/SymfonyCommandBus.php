<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

#[AsAlias(CommandBus::class)]
final readonly class SymfonyCommandBus implements CommandBus
{
    public function __construct(
        #[Autowire(service: 'command.bus')]
        private MessageBusInterface $messageBus,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        try {
            return $this->messageBus->dispatch($command)->last(HandledStamp::class)?->getResult();
        } catch (HandlerFailedException $exception) {
            foreach ($exception->getWrappedExceptions() as $wrappedException) {
                throw $wrappedException;
            }

            throw $exception;
        }
    }
}
