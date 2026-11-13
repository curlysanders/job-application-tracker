<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisRequestRepository;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisState;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

final readonly class VacancyExtractionStatusController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private Security $security,
        private AnalysisRequestRepository $requests,
        private Environment $twig,
    ) {
    }

    #[Route('/vacancy-extractions/{id}', name: 'app_vacancy_extraction_status', methods: ['GET'])]
    public function __invoke(string $id): Response
    {
        $user = $this->requireAuthenticatedUser('Vacancy extraction requires an authenticated user.');
        $analysis = $this->requests->findOwnedBy($id, $user->id->toRfc4122());
        if (null === $analysis || AnalysisKind::VacancyExtraction !== $analysis->kind) {
            throw new NotFoundHttpException('Vacancy extraction not found.');
        }

        return new Response($this->twig->render('vacancy/extraction_status.html.twig', [
            'analysis' => $analysis,
            'poll' => AnalysisState::Pending === $analysis->state,
        ]));
    }
}
