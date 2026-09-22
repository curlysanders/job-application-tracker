<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model\Company;

use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\DirectContactData;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;
    #[Assert\Url]
    #[Assert\Length(max: 2048)]
    public ?string $website = null;
    #[Assert\Length(max: 255)]
    public ?string $industry = null;
    /** @var list<DirectContactData> */
    #[Assert\Valid]
    public array $directContacts = [];
}
