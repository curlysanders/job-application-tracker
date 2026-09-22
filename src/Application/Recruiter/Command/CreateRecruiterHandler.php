<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Recruiter\Command;

use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;
use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;

final readonly class CreateRecruiterHandler implements CommandHandler
{
    public function __construct(private RecruiterRepository $recruiters)
    {
    }

    public function __invoke(CreateRecruiter $command): Recruiter
    {
        $recruiter = new Recruiter($command->agencyName, $command->website);
        $recruiter->replaceDirectContacts(
            ...array_map(
                static fn (DirectContactInput $contact): DirectContact => new DirectContact(
                    $contact->name,
                    $contact->email,
                    $contact->phone,
                    $contact->linkedinUrl
                ),
                $command->directContacts,
            ),
        );
        $this->recruiters->save($recruiter);

        return $recruiter;
    }
}
