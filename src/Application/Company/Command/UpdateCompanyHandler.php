<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Company\Command;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Contact\DirectContact;

final readonly class UpdateCompanyHandler implements CommandHandler
{
    public function __construct(private CompanyRepository $companies)
    {
    }

    public function __invoke(UpdateCompany $command): Company
    {
        $company = $this->companies->find($command->companyId) ?? throw new \LogicException('The company no longer exists.');
        $company->update($command->name, $command->website, $command->industry);
        $company->replaceDirectContacts(
            ...array_map(
                static fn (DirectContactInput $contact): DirectContact => new DirectContact(
                    $contact->name,
                    $contact->email,
                    $contact->phone,
                    $contact->linkedinUrl,
                ),
                $command->directContacts,
            ),
        );
        $this->companies->save($company);

        return $company;
    }
}
