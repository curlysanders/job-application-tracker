<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Persistence\Doctrine;

use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\User\UserRegistered;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\JsonDomainEventType;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use PHPUnit\Framework\TestCase;

final class JsonDomainEventTypeTest extends TestCase
{
    public function testStoresNamedDomainEventsAsReadableVersionedJson(): void
    {
        $user = new User()->setEmail('sander@example.test');
        $user->recordRegistered();
        $event = $user->getRecordedEvents()[0];
        $type = new JsonDomainEventType();

        $json = $type->convertToDatabaseValue($event, new MariaDBPlatform());

        self::assertSame([
            'eventType' => UserRegistered::class,
            'eventVersion' => 1,
            'entityId' => $user->getId()->toRfc4122(),
            'occurredAt' => $event->getOccurredAt()->format('Y-m-d\\TH:i:s.uP'),
            'changedProperties' => [],
        ], json_decode($json, true, flags: JSON_THROW_ON_ERROR));

        $restored = $type->convertToPHPValue($json, new MariaDBPlatform());

        self::assertInstanceOf(UserRegistered::class, $restored);
        self::assertSame($event->getEntityId(), $restored->getEntityId());
        self::assertSame($event->getOccurredAt()->format('Y-m-d\\TH:i:s.uP'), $restored->getOccurredAt()->format('Y-m-d\\TH:i:s.uP'));
        self::assertSame([], $restored->changedProperties);
    }
}
