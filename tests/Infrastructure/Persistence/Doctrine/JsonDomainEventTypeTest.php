<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Persistence\Doctrine;

use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\User\UserRegistered;
use CurlySanders\JobApplicationTracker\Infrastructure\Messaging\LingodaDomainEvent;
use CurlySanders\JobApplicationTracker\Infrastructure\Persistence\Doctrine\JsonDomainEventType;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Types\Exception\ValueNotConvertible;
use PHPUnit\Framework\TestCase;

final class JsonDomainEventTypeTest extends TestCase
{
    public function testStoresNamedDomainEventsAsReadableVersionedJson(): void
    {
        $user = new User()->setEmail('sander@example.test');
        $user->recordRegistered();
        $event = $user->getRecordedEvents()[0];
        $type = new JsonDomainEventType();

        $json = $type->convertToDatabaseValue(new LingodaDomainEvent($event), new MariaDBPlatform());

        self::assertSame([
            'eventType' => UserRegistered::class,
            'eventVersion' => 1,
            'entityId' => $user->getId()->toRfc4122(),
            'occurredAt' => $event->occurredAt()->format('Y-m-d\\TH:i:s.uP'),
            'changedProperties' => [],
        ], json_decode($json, true, flags: JSON_THROW_ON_ERROR));

        $restored = $type->convertToPHPValue($json, new MariaDBPlatform());

        self::assertInstanceOf(LingodaDomainEvent::class, $restored);
        $restoredEvent = $restored->event();
        self::assertInstanceOf(UserRegistered::class, $restoredEvent);
        self::assertSame($event->aggregateId()->toRfc4122(), $restoredEvent->aggregateId()->toRfc4122());
        self::assertSame($event->occurredAt()->format('Y-m-d\\TH:i:s.uP'), $restoredEvent->occurredAt()->format('Y-m-d\\TH:i:s.uP'));
        self::assertSame([], $restoredEvent->changedProperties);
    }

    public function testRejectsNonEventsAndMalformedPayloads(): void
    {
        $type = new JsonDomainEventType();
        $platform = new MariaDBPlatform();

        $type->convertToDatabaseValue(null, $platform);
        self::assertNull($type->convertToPHPValue('', $platform));

        try {
            $type->convertToDatabaseValue(new \stdClass(), $platform);
            self::fail('Expected a non-event value to be rejected.');
        } catch (ValueNotConvertible) {
        }

        $this->expectException(ValueNotConvertible::class);
        $type->convertToPHPValue('{"eventType":"stdClass","eventVersion":1}', $platform);
    }
}
