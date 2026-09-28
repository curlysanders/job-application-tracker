<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\PipelineStatuses;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Pipeline\VacancyPipelineRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\VacancyStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class DashboardPipelineVacanciesController
{
    public function __construct(private Security $security, private VacancyPipelineRepository $pipeline, private Environment $twig)
    {
    }

    #[Route('/app/pipeline/vacancies', name: 'app_dashboard_pipeline_vacancies', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser();
        $userId = $user->getId() ?? throw new \LogicException('The dashboard requires a persisted user.');

        return new Response($this->twig->render('dashboard/_vacancies.html.twig', [
            'pipeline' => $this->pipeline->forUser($userId, $this->selectedStatus($request)),
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

    private function selectedStatus(Request $request): ?VacancyStatus
    {
        try {
            return PipelineStatuses::fromFilter($request->query->getString('status'));
        } catch (\InvalidArgumentException $exception) {
            throw new NotFoundHttpException('Vacancy status not found.', $exception);
        }
    }
}
