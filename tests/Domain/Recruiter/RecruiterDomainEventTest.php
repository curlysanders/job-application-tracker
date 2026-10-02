<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Recruiter;

use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\RecruiterCreated;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class RecruiterDomainEventTest extends TestCase
{
    public function testRecruiterFactsExcludeDirectContactData(): void
    {
        $recruiter = new Recruiter(new User(), 'Acme Talent', 'https://example.test');
        $recruiter->replaceDirectContacts(new DirectContact('Jane Doe', 'jane@example.test', '+31612345678', 'https://linkedin.example.test/jane'));
        $recruiter->recordCreated();

        $event = $recruiter->getRecordedEvents()[0];

        self::assertInstanceOf(RecruiterCreated::class, $event);
        self::assertSame(['agencyName' => 'Acme Talent', 'website' => 'https://example.test'], $event->changedProperties);
    }
}
