<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Recruiter\Command;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactFactory;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;

final readonly class CreateRecruiterHandler implements CommandHandler
{
    public function __construct(private UserRepository $users, private RecruiterRepository $recruiters)
    {
    }

    public function __invoke(CreateRecruiter $command): Recruiter
    {
        $user = $this->users->find($command->userId) ?? throw new \LogicException('The authenticated user no longer exists.');
        $recruiter = new Recruiter($user, $command->agencyName, $command->website);
        $recruiter->replaceDirectContacts(...DirectContactFactory::fromInputs($command->directContacts));
        $recruiter->recordCreated();
        $this->recruiters->save($recruiter);

        return $recruiter;
    }
}
