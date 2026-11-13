<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

final class VacancyExtractionSubmissionData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100_000)]
    public ?string $vacancyText = null;
}
