<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Company\Command;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactFactory;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;

final readonly class UpdateCompanyHandler implements CommandHandler
{
    public function __construct(private CompanyRepository $companies)
    {
    }

    public function __invoke(UpdateCompany $command): Company
    {
        $company = $this->companies->findOwnedBy($command->companyId, $command->userId) ?? throw new \LogicException('The company no longer exists.');
        $company->update($command->name, $command->website, $command->industry);
        $company->replaceDirectContacts(...DirectContactFactory::fromInputs($command->directContacts));
        $company->recordDetailsUpdated();
        $this->companies->save($company);

        return $company;
    }
}
