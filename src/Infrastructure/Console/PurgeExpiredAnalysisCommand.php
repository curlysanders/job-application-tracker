<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Console;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisRequestRepository;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:ai:purge-expired-analysis', description: 'Delete expired encrypted AI analysis drafts and results.')]
final class PurgeExpiredAnalysisCommand extends Command
{
    public function __construct(
        private readonly AnalysisRequestRepository $requests,
        private readonly VacancyAuthoringDraftRepository $drafts,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $now = new \DateTimeImmutable();
        $requests = $this->requests->purgeExpired($now);
        $drafts = $this->drafts->purgeExpired($now);
        new SymfonyStyle($input, $output)->success(sprintf('Purged %d expired AI analysis request(s) and %d authoring draft(s).', $requests, $drafts));

        return Command::SUCCESS;
    }
}
