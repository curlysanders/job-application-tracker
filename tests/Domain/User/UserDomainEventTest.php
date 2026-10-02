<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\User;

use CurlySanders\JobApplicationTracker\Domain\User\PreferredSalary;
use CurlySanders\JobApplicationTracker\Domain\User\PreferredTransportMode;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\User\UserPreferencesUpdated;
use PHPUnit\Framework\TestCase;

final class UserDomainEventTest extends TestCase
{
    public function testPreferenceUpdateRecordsNamedFactWithoutSensitivePreferenceValues(): void
    {
        $user = new User();
        $user->updatePreferences(PreferredSalary::fromDecimal('4500.00', 'EUR'), 45, PreferredTransportMode::PublicTransport);

        self::assertCount(1, $user->getRecordedEvents());
        $event = $user->getRecordedEvents()[0];
        self::assertInstanceOf(UserPreferencesUpdated::class, $event);
        self::assertSame([], $event->changedProperties);
    }
}
