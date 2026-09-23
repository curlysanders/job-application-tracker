<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Console;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Application\TechStack\Command\SeedStandardTechStacks;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:tech-stack:seed', description: 'Seed the standard technology catalogue.')]
final class SeedTechStacksCommand extends Command
{
    public function __construct(private readonly CommandBus $commandBus)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $created = $this->commandBus->dispatch(new SeedStandardTechStacks());
        if (!is_int($created)) {
            throw new \LogicException('The technology seed command must return the number of created tags.');
        }

        $output->writeln(sprintf('Seeded %d technology tags.', $created));

        return Command::SUCCESS;
    }
}
