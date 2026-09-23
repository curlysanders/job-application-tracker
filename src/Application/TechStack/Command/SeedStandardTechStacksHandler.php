<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\TechStack\Command;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandHandler;
use CurlySanders\JobApplicationTracker\Application\TechStack\StandardTechStacks;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;

final readonly class SeedStandardTechStacksHandler implements CommandHandler
{
    public function __construct(private TechStackRepository $techStacks)
    {
    }

    public function __invoke(SeedStandardTechStacks $command): int
    {
        $created = 0;
        foreach (StandardTechStacks::all() as $category => $names) {
            foreach ($names as $name) {
                if (null !== $this->techStacks->findBySlug(TechStack::slug($name))) {
                    continue;
                }

                $this->techStacks->save(new TechStack($name, $category));
                ++$created;
            }
        }

        return $created;
    }
}
