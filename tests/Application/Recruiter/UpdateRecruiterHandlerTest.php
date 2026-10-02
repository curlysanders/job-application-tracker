<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\Command\UpdateRecruiter;
use CurlySanders\JobApplicationTracker\Application\Recruiter\Command\UpdateRecruiterHandler;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;
use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class UpdateRecruiterHandlerTest extends TestCase
{
    private const string RECRUITER_ID = '018f8e3e-1234-7abc-8def-0123456789ab';
    private const string USER_ID = '018f8e3e-5678-7abc-8def-0123456789ab';

    public function testUpdatesARecruiterAndReplacesItsContacts(): void
    {
        $recruiter = new Recruiter(new User(), 'Talent Partners', null);
        $repository = $this->createMock(RecruiterRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(self::RECRUITER_ID, self::USER_ID)->willReturn($recruiter);
        $repository->expects(self::once())->method('save')->with($recruiter);
        $command = new UpdateRecruiter(self::USER_ID, self::RECRUITER_ID, 'Talent Europe', 'https://talent-europe.example', [
            new DirectContactInput('Ada Recruiter', 'ada@talent.example', '+31 6 12345678', 'https://www.linkedin.com/in/ada'),
        ]);

        $result = new UpdateRecruiterHandler($repository)($command);

        self::assertSame($recruiter, $result);
        self::assertSame('Talent Europe', $recruiter->getAgencyName());
        self::assertSame('https://talent-europe.example', $recruiter->getWebsite());
        self::assertCount(1, $recruiter->getDirectContacts());
        $contact = $recruiter->getDirectContacts()->first();
        self::assertInstanceOf(DirectContact::class, $contact);
        self::assertSame('Ada Recruiter', $contact->getName());
    }

    public function testRejectsUpdatesForADeletedRecruiter(): void
    {
        $repository = $this->createMock(RecruiterRepository::class);
        $repository->expects(self::once())->method('findOwnedBy')->with(self::RECRUITER_ID, self::USER_ID)->willReturn(null);

        $this->expectException(\LogicException::class);
        new UpdateRecruiterHandler($repository)(new UpdateRecruiter(self::USER_ID, self::RECRUITER_ID, 'Talent Europe', null, []));
    }
}
