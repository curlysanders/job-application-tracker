<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model\Recruiter;

use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\DirectContactData;
use Symfony\Component\Validator\Constraints as Assert;

final class RecruiterData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $agencyName = null;
    #[Assert\Url]
    #[Assert\Length(max: 2048)]
    public ?string $website = null;
    /** @var list<DirectContactData> */
    #[Assert\Valid]
    public array $directContacts = [];
}
