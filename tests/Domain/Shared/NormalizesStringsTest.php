<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Shared;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use PHPUnit\Framework\TestCase;

final class NormalizesStringsTest extends TestCase
{
    public function testEntitiesTrimRequiredAndOptionalStrings(): void
    {
        $company = new Company('  Acme BV  ', '  https://acme.example  ', '  Software  ');
        $recruiter = new Recruiter('  Talent Partners  ', '  https://talent.example  ');
        $contact = new DirectContact('  Ada Lovelace  ', '  ada@example.com  ', '  +31 6 12345678  ', '  https://www.linkedin.com/in/ada  ');

        self::assertSame('Acme BV', $company->getName());
        self::assertSame('https://acme.example', $company->getWebsite());
        self::assertSame('Software', $company->getIndustry());
        self::assertSame('Talent Partners', $recruiter->getAgencyName());
        self::assertSame('https://talent.example', $recruiter->getWebsite());
        self::assertSame('Ada Lovelace', $contact->getName());
        self::assertSame('ada@example.com', $contact->getEmail());
        self::assertSame('+31 6 12345678', $contact->getPhone());
        self::assertSame('https://www.linkedin.com/in/ada', $contact->getLinkedinUrl());
    }

    public function testEntitiesConvertBlankOptionalStringsToNull(): void
    {
        $company = new Company('Acme BV', '   ', "\t");
        $recruiter = new Recruiter('Talent Partners', "\n");
        $contact = new DirectContact('Ada Lovelace', ' ', '  ', "\t");

        self::assertNull($company->getWebsite());
        self::assertNull($company->getIndustry());
        self::assertNull($recruiter->getWebsite());
        self::assertNull($contact->getEmail());
        self::assertNull($contact->getPhone());
        self::assertNull($contact->getLinkedinUrl());
    }

    public function testEntitiesRejectBlankRequiredStringsWithTheirOwnMessages(): void
    {
        $cases = [
            [static fn (): Company => new Company(' ', null, null), 'A company name is required.'],
            [static fn (): Recruiter => new Recruiter("\t", null), 'An agency name is required.'],
            [static fn (): DirectContact => new DirectContact("\n", null, null, null), 'A contact name is required.'],
        ];

        foreach ($cases as [$factory, $message]) {
            try {
                $factory();
                self::fail('Expected the required string invariant to reject blank input.');
            } catch (\InvalidArgumentException $exception) {
                self::assertSame($message, $exception->getMessage());
            }
        }
    }
}
