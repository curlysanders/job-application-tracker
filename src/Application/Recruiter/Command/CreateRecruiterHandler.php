<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Recruiter\Command;

use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactFactory;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;

final readonly class CreateRecruiterHandler implements CommandHandler
{
    public function __construct(private RecruiterRepository $recruiters)
    {
    }

    public function __invoke(CreateRecruiter $command): Recruiter
    {
        $recruiter = new Recruiter($command->agencyName, $command->website);
        $recruiter->replaceDirectContacts(...DirectContactFactory::fromInputs($command->directContacts));
        $this->recruiters->save($recruiter);

        return $recruiter;
    }
}
