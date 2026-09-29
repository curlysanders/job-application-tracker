<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model\Company;

use CurlySanders\JobApplicationTracker\UI\Form\Model\Contact\ContactableData;
use Symfony\Component\Validator\Constraints as Assert;

final class CompanyData extends ContactableData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;
    #[Assert\Length(max: 255)]
    public ?string $industry = null;
}
