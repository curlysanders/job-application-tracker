<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Company;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Company\CompanyCreated;
use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use PHPUnit\Framework\TestCase;

final class CompanyDomainEventTest extends TestCase
{
    public function testCompanyFactsExcludeDirectContactData(): void
    {
        $company = new Company('Acme', 'https://example.test', 'Software');
        $company->replaceDirectContacts(new DirectContact('Jane Doe', 'jane@example.test', '+31612345678', 'https://linkedin.example.test/jane'));
        $company->recordCreated();

        $event = $company->getRecordedEvents()[0];

        self::assertInstanceOf(CompanyCreated::class, $event);
        self::assertSame(['name' => 'Acme', 'website' => 'https://example.test', 'industry' => 'Software'], $event->changedProperties);
    }
}
