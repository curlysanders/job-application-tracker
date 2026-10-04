<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ValidatePendingResume;
use CurlySanders\JobApplicationTracker\Domain\User\ResumeValidationRequested;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'event.bus')]
final readonly class ValidateUploadedResume
{
    public function __construct(private ValidatePendingResume $validatePendingResume)
    {
    }

    public function __invoke(ResumeValidationRequested $event): void
    {
        $validationId = $event->changedProperties['validationId'] ?? null;
        if (!is_string($validationId) || !Uuid::isValid($validationId)) {
            throw new \LogicException('A resume validation request must include a UUID validation ID.');
        }

        ($this->validatePendingResume)($event->getEntityId(), $validationId);
    }
}
