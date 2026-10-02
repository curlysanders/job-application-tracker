<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Company\Command;

use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactFactory;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;

final readonly class CreateCompanyHandler implements CommandHandler
{
    public function __construct(private UserRepository $users, private CompanyRepository $companies)
    {
    }

    public function __invoke(CreateCompany $command): Company
    {
        $user = $this->users->find($command->userId) ?? throw new \LogicException('The authenticated user no longer exists.');
        $company = new Company($user, $command->name, $command->website, $command->industry);
        $company->replaceDirectContacts(...DirectContactFactory::fromInputs($command->directContacts));
        $company->recordCreated();
        $this->companies->save($company);

        return $company;
    }
}
