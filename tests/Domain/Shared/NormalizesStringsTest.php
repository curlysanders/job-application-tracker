<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Shared;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class NormalizesStringsTest extends TestCase
{
    public function testEntitiesTrimRequiredAndOptionalStrings(): void
    {
        $company = new Company(new User(), '  Acme BV  ', '  https://acme.example  ', '  Software  ');
        $recruiter = new Recruiter(new User(), '  Talent Partners  ', '  https://talent.example  ');
        $contact = new DirectContact('  Ada Lovelace  ', '  ada@example.com  ', '  +31 6 12345678  ', '  https://www.linkedin.com/in/ada  ');

        self::assertSame('Acme BV', $company->name);
        self::assertSame('https://acme.example', $company->website);
        self::assertSame('Software', $company->industry);
        self::assertSame('Talent Partners', $recruiter->agencyName);
        self::assertSame('https://talent.example', $recruiter->website);
        self::assertSame('Ada Lovelace', $contact->name);
        self::assertSame('ada@example.com', $contact->email);
        self::assertSame('+31 6 12345678', $contact->phone);
        self::assertSame('https://www.linkedin.com/in/ada', $contact->linkedinUrl);
    }

    public function testEntitiesConvertBlankOptionalStringsToNull(): void
    {
        $company = new Company(new User(), 'Acme BV', '   ', "\t");
        $recruiter = new Recruiter(new User(), 'Talent Partners', "\n");
        $contact = new DirectContact('Ada Lovelace', ' ', '  ', "\t");

        self::assertNull($company->website);
        self::assertNull($company->industry);
        self::assertNull($recruiter->website);
        self::assertNull($contact->email);
        self::assertNull($contact->phone);
        self::assertNull($contact->linkedinUrl);
    }

    public function testEntitiesRejectBlankRequiredStringsWithTheirOwnMessages(): void
    {
        $cases = [
            [static fn (): Company => new Company(new User(), ' ', null, null), 'A company name is required.'],
            [static fn (): Recruiter => new Recruiter(new User(), "\t", null), 'An agency name is required.'],
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
