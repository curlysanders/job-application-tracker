<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Contact;

use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\Shared\WebAddress;

final class DirectContact
{
    use NormalizesStrings;

    private(set) ?int $id = null;

    private(set) ?Company $company = null;

    private(set) ?Recruiter $recruiter = null;

    private(set) string $name;

    private(set) ?string $email;

    private(set) ?string $phone;

    private(set) ?string $linkedinUrl;

    public function __construct(
        string $name,
        ?string $email,
        ?string $phone,
        ?string $linkedinUrl,
    ) {
        $this->name = self::required($name, 'A contact name is required.');
        $this->email = self::optional($email);
        $this->phone = self::optional($phone);
        $this->linkedinUrl = WebAddress::optionalHttpUrl($linkedinUrl, 'A contact LinkedIn URL');
        if (null !== $this->email && false === filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A contact email address must be valid.');
        }
    }

    public function assignToCompany(Company $company): void
    {
        if (null !== $this->recruiter) {
            throw new \LogicException('A direct contact cannot belong to both a company and a recruiter.');
        }

        $this->company = $company;
    }

    public function assignToRecruiter(Recruiter $recruiter): void
    {
        if (null !== $this->company) {
            throw new \LogicException('A direct contact cannot belong to both a company and a recruiter.');
        }

        $this->recruiter = $recruiter;
    }
}
