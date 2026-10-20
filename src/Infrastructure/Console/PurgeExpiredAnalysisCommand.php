<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Console;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisRequestRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:ai:purge-expired-analysis', description: 'Delete expired encrypted AI analysis drafts and results.')]
final class PurgeExpiredAnalysisCommand extends Command
{
    public function __construct(private readonly AnalysisRequestRepository $requests)
    {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $deleted = $this->requests->purgeExpired(new \DateTimeImmutable());
        new SymfonyStyle($input, $output)->success(sprintf('Purged %d expired AI analysis request(s).', $deleted));

        return Command::SUCCESS;
    }
}
