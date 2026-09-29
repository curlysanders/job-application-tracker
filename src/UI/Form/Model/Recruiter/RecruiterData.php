<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model\Recruiter;

use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\ContactableData;
use Symfony\Component\Validator\Constraints as Assert;

final class RecruiterData extends ContactableData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $agencyName = null;
}
