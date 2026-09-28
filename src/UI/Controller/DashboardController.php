<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Overview\VacancyOverviewRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\PipelineStatuses;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\VacancyPipelineRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\UI\Dashboard\VacancyOverviewRequest;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class DashboardController
{
    public function __construct(
        private Security $security,
        private VacancyPipelineRepository $pipeline,
        private VacancyOverviewRepository $overview,
        private Environment $twig,
    ) {
    }

    #[Route('/app', name: 'app_dashboard', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser();
        $userId = $user->getId() ?? throw new \LogicException('The dashboard requires a persisted user.');
        $overviewRequest = VacancyOverviewRequest::fromRequest($request);

        return new Response($this->twig->render('dashboard/index.html.twig', [
            'pipeline' => $this->pipeline->forUser($userId, $overviewRequest->filter->status),
            'pipelineStatuses' => PipelineStatuses::all(),
            'selectedStatus' => $overviewRequest->filter->status,
            'overview' => $this->overview->forUser($userId, $user->getPreferredSalary(), $overviewRequest->filter),
            'filter' => $overviewRequest->filter,
            'query' => $overviewRequest->query,
        ]));
    }

    private function authenticatedUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('The dashboard requires an authenticated user.');
        }

        return $user;
    }
}
