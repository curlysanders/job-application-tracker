<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\TechStack\StandardTechStacks;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;

final readonly class StandardTechnologyResolver
{
    public function __construct(private TechStackRepository $techStacks)
    {
    }

    public function find(string $name): ?TechStack
    {
        try {
            $slug = TechStack::slug($name);
        } catch (\InvalidArgumentException) {
            return null;
        }
        $standardNames = StandardTechStacks::all()
                |> array_values(...)
                |> (static fn ($x) => array_merge(...$x));
        if (!in_array($name, $standardNames, true)) {
            return null;
        }

        return $this->techStacks->findBySlug($slug);
    }
}
