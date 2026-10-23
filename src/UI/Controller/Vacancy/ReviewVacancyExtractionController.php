<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisRequestRepository;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftService;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionReviewService;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisState;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

final readonly class ReviewVacancyExtractionController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private Security $security,
        private AnalysisRequestRepository $requests,
        private VacancyExtractionReviewService $reviews,
        private VacancyAuthoringDraftService $drafts,
        private CsrfTokenManagerInterface $csrf,
        private Environment $twig,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/vacancy-extractions/{id}/review', name: 'app_vacancy_extraction_review', methods: ['GET', 'POST'])]
    public function __invoke(
        string $id,
        Request $request,
    ): Response {
        $user = $this->requireAuthenticatedUser('Vacancy extraction requires an authenticated user.');
        $analysis = $this->requests->findOwnedBy($id, $user->id->toRfc4122());
        if (null === $analysis || AnalysisKind::VacancyExtraction !== $analysis->kind || AnalysisState::Completed !== $analysis->state || $analysis->expiresAt <= new \DateTimeImmutable()) {
            throw new NotFoundHttpException('Vacancy extraction not found.');
        }
        $review = $this->reviews->review($analysis);
        if ($request->isMethod('POST')) {
            if (!$this->csrf->isTokenValid(new CsrfToken('vacancy_extraction_review_'.$id, $request->request->getString('_token')))) {
                throw new AccessDeniedHttpException('Invalid vacancy extraction review token.');
            }
            $selectedFields = [];
            foreach ($request->request->all('field') as $field => $key) {
                if (!is_string($field) || !is_string($key)) {
                    throw new \InvalidArgumentException('Invalid vacancy extraction review selection.');
                }
                $selectedFields[$field] = $key;
            }
            $selectedTechStacks = [];
            foreach ($request->request->all('techStack') as $key) {
                if (!is_string($key)) {
                    throw new \InvalidArgumentException('Invalid vacancy extraction review selection.');
                }
                $selectedTechStacks[] = $key;
            }
            $data = $this->reviews->vacancyData($review, $selectedFields, $selectedTechStacks);
            $this->reviews->applyOrganizationCandidates($data, $review, $user->id->toRfc4122());
            $draft = $this->drafts->create($user, $data);

            return new RedirectResponse($this->urls->generate('app_vacancy_create', ['draft' => $draft->id->toRfc4122()]));
        }

        return new Response($this->twig->render('vacancy/extraction_review.html.twig', ['analysis' => $analysis, 'review' => $review]));
    }
}
