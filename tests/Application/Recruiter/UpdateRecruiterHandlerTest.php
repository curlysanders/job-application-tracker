<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Recruiter;

use CurlySanders\JobApplicationTracker\Application\Recruiter\Command\UpdateRecruiter;
use CurlySanders\JobApplicationTracker\Application\Recruiter\Command\UpdateRecruiterHandler;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;
use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use PHPUnit\Framework\TestCase;

final class UpdateRecruiterHandlerTest extends TestCase
{
    public function testUpdatesARecruiterAndReplacesItsContacts(): void
    {
        $recruiter = new Recruiter('Talent Partners', null);
        $repository = $this->createMock(RecruiterRepository::class);
        $repository->expects(self::once())->method('find')->with(4)->willReturn($recruiter);
        $repository->expects(self::once())->method('save')->with($recruiter);
        $command = new UpdateRecruiter(4, 'Talent Europe', 'https://talent-europe.example', [
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
        $repository->expects(self::once())->method('find')->with(4)->willReturn(null);

        $this->expectException(\LogicException::class);
        new UpdateRecruiterHandler($repository)(new UpdateRecruiter(4, 'Talent Europe', null, []));
    }
}
