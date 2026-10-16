<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class ValidationInvariantTest extends TestCase
{
    public function testUserRejectsAnInvalidEmailAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new User()->setEmail('not-an-email');
    }

    public function testContactRejectsAnInvalidLinkedinUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DirectContact('Ada', 'ada@example.com', null, 'linkedin.com/in/ada');
    }

    public function testCompanyRejectsANonHttpWebsite(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Company(new User(), 'Example', 'ftp://example.com', null);
    }
}
