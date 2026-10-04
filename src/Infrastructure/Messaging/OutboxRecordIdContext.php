<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Messaging;

final class OutboxRecordIdContext
{
    private ?int $id = null;

    public function replace(?int $id): ?int
    {
        $previous = $this->id;
        $this->id = $id;

        return $previous;
    }

    public function id(): ?int
    {
        return $this->id;
    }
}
