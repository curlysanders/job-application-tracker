<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine;

use Carbon\CarbonImmutable;
use CurlySanders\JobApplicationTracker\Domain\Shared\NamedDomainEvent;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use Doctrine\DBAL\Types\JsonType;

final class JsonDomainEventType extends JsonType
{
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof NamedDomainEvent) {
            throw ValueNotConvertible::new($value, 'byte_object', 'Only named domain events can be stored in the outbox.');
        }

        return parent::convertToDatabaseValue([
            'eventType' => $value::class,
            'eventVersion' => 1,
            'entityId' => $value->getEntityId(),
            'occurredAt' => $value->getOccurredAt()->format('Y-m-d\\TH:i:s.uP'),
            'changedProperties' => $value->changedProperties,
        ], $platform);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?NamedDomainEvent
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $payload = parent::convertToPHPValue($value, $platform);
        if (!is_array($payload)) {
            throw ValueNotConvertible::new($value, 'byte_object', 'The outbox event payload must be a JSON object.');
        }

        $eventType = $payload['eventType'] ?? null;
        $eventVersion = $payload['eventVersion'] ?? null;
        $entityId = $payload['entityId'] ?? null;
        $occurredAt = $payload['occurredAt'] ?? null;
        $changedProperties = $payload['changedProperties'] ?? null;
        if (
            1 !== $eventVersion
            || !is_string($eventType)
            || !is_string($entityId)
            || !is_string($occurredAt)
            || !is_array($changedProperties)
            || !is_a($eventType, NamedDomainEvent::class, true)
        ) {
            throw ValueNotConvertible::new($value, 'byte_object', 'The outbox event payload is not a versioned named domain event.');
        }

        $validatedChangedProperties = [];
        foreach ($changedProperties as $property => $changedProperty) {
            if (!is_string($property)) {
                throw ValueNotConvertible::new($value, 'byte_object', 'The changed properties must use string keys.');
            }

            $validatedChangedProperties[$property] = $changedProperty;
        }

        try {
            return $eventType::reconstitute($entityId, CarbonImmutable::parse($occurredAt), $validatedChangedProperties);
        } catch (\Throwable $exception) {
            throw ValueNotConvertible::new($value, 'byte_object', $exception->getMessage(), $exception);
        }
    }
}
