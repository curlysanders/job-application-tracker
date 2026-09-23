<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model\TechStack;

use Symfony\Component\Validator\Constraints as Assert;

final class TechStackTagsData
{
    #[Assert\Length(max: 4096)]
    public ?string $existingTags = null;

    /** @var list<NewTechStackData> */
    #[Assert\Valid]
    public array $newTags = [];
}
