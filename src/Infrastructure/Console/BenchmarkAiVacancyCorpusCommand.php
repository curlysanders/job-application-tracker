<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Console;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Infrastructure\Ai\VacancyCorpusEvaluator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:ai:benchmark-vacancy-corpus', description: 'Evaluate the selected vacancy extraction provider against the curated corpus.')]
final readonly class BenchmarkAiVacancyCorpusCommand
{
    public function __construct(
        private VacancyExtractionProvider $provider,
        private VacancyCorpusEvaluator $evaluator,
        #[Autowire(param: 'kernel.project_dir')]
        private string $projectDir,
    ) {
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $content = file_get_contents($this->projectDir.'/resources/ai/vacancy-corpus.json');
        $cases = is_string($content) ? json_decode($content, true, flags: JSON_THROW_ON_ERROR) : throw new \RuntimeException('The vacancy corpus could not be read.');
        if (!is_array($cases)) {
            throw new \RuntimeException('The vacancy corpus has an invalid format.');
        }

        /** @var list<array{id: string, text: string, expected: array<string, string|list<string>>}> $cases */
        $result = $this->evaluator->evaluate($cases, $this->provider);
        $output->writeln(json_encode($result, JSON_THROW_ON_ERROR));

        return $result['passed'] ? Command::SUCCESS : Command::FAILURE;
    }
}
