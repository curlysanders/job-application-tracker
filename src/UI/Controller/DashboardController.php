<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\VacancyOverviewRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\PipelineStatuses;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\VacancyPipelineRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Reminder\DueVacancyReminderRepository;
use CurlySanders\JobApplicationTracker\UI\Dashboard\VacancyOverviewRequest;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class DashboardController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private Security $security,
        private VacancyPipelineRepository $pipeline,
        private VacancyOverviewRepository $overview,
        private DueVacancyReminderRepository $reminders,
        private ClockInterface $clock,
        private Environment $twig,
    ) {
    }

    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->currentUser();
        if (null === $user) {
            return new Response($this->twig->render('home/index.html.twig'));
        }
        $userId = $user->getId()->toRfc4122();
        $overviewRequest = VacancyOverviewRequest::fromRequest($request);
        $tomorrow = $this->clock->now()->setTime(0, 0)->modify('+1 day');

        return new Response($this->twig->render('dashboard/index.html.twig', [
            'pipeline' => $this->pipeline->forUser($userId, $overviewRequest->filter->status),
            'pipelineStatuses' => PipelineStatuses::all(),
            'selectedStatus' => $overviewRequest->filter->status,
            'overview' => $this->overview->forUser($userId, $user->getPreferredSalary(), $overviewRequest->filter),
            'reminders' => $this->reminders->forUserDueBefore($userId, $tomorrow),
            'filter' => $overviewRequest->filter,
            'query' => $overviewRequest->query,
        ]));
    }
}
