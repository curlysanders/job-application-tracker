<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use Lingoda\DomainEventsBundle\Infra\Symfony\Messenger\Transport\OutboxRecordIdStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final readonly class OutboxRecordIdContextMiddleware implements MiddlewareInterface
{
    public function __construct(private OutboxRecordIdContext $context)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $stamp = $envelope->last(OutboxRecordIdStamp::class);
        $recordId = is_object($stamp) ? $stamp->getRecordId() : null;
        $previous = $this->context->replace(is_int($recordId) ? $recordId : null);

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $this->context->replace($previous);
        }
    }
}
