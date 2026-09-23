<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model\TechStack;

use Symfony\Component\Validator\Constraints as Assert;

final class NewTechStackData
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $category = null;
}
