<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Recruiter\Command;

use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactFactory;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;

final readonly class UpdateRecruiterHandler implements CommandHandler
{
    public function __construct(private RecruiterRepository $recruiters)
    {
    }

    public function __invoke(UpdateRecruiter $command): Recruiter
    {
        $recruiter = $this->recruiters->find($command->recruiterId) ?? throw new \LogicException('The recruiter no longer exists.');
        $recruiter->update($command->agencyName, $command->website);
        $recruiter->replaceDirectContacts(...DirectContactFactory::fromInputs($command->directContacts));
        $this->recruiters->save($recruiter);

        return $recruiter;
    }
}
