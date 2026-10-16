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

    public function testRejectsChangedPropertiesWithNonStringKeys(): void
    {
        $type = new JsonDomainEventType();
        $payload = [
            'eventType' => UserRegistered::class,
            'eventVersion' => 1,
            'entityId' => '018f0957-8a79-7fcb-bab5-2ccfbe98de81',
            'occurredAt' => '2026-10-16T12:00:00.000000+00:00',
            'changedProperties' => ['1' => 'invalid'],
        ];

        $this->expectException(ValueNotConvertible::class);
        $type->convertToPHPValue(json_encode($payload, JSON_THROW_ON_ERROR), new MariaDBPlatform());
    }

    public function testRejectsAnUnparseableOccurrenceTimestamp(): void
    {
        $type = new JsonDomainEventType();
        $payload = [
            'eventType' => UserRegistered::class,
            'eventVersion' => 1,
            'entityId' => '018f0957-8a79-7fcb-bab5-2ccfbe98de81',
            'occurredAt' => 'not-a-timestamp',
            'changedProperties' => [],
        ];

        $this->expectException(ValueNotConvertible::class);
        $type->convertToPHPValue(json_encode($payload, JSON_THROW_ON_ERROR), new MariaDBPlatform());
    }

    public function testRejectsAJsonPayloadThatIsNotAnObject(): void
    {
        $this->expectException(ValueNotConvertible::class);

        new JsonDomainEventType()->convertToPHPValue('null', new MariaDBPlatform());
    }
}
