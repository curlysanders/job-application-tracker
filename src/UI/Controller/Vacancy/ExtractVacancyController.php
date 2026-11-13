<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Ai\Command\QueueAnalysisRequest;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use CurlySanders\JobApplicationTracker\UI\Form\Model\VacancyExtractionSubmissionData;
use CurlySanders\JobApplicationTracker\UI\Form\VacancyExtractionSubmissionType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class ExtractVacancyController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private Security $security,
        private FormFactoryInterface $forms,
        private CommandBus $commands,
        private Environment $twig,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/vacancies/extract', name: 'app_vacancy_extract', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->requireAuthenticatedUser('Vacancy extraction requires an authenticated user.');
        $data = new VacancyExtractionSubmissionData();
        $form = $this->forms->create(VacancyExtractionSubmissionType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $analysis = $this->commands->dispatch(new QueueAnalysisRequest($user->id->toRfc4122(), AnalysisKind::VacancyExtraction, $data->vacancyText ?? ''));
            if (!$analysis instanceof AnalysisRequest) {
                throw new \LogicException('Queuing a vacancy extraction must return an analysis request.');
            }

            return new RedirectResponse($this->urls->generate('app_vacancy_extraction_status', ['id' => $analysis->id->toRfc4122()]));
        }

        return new Response($this->twig->render('vacancy/extract.html.twig', ['form' => $form->createView()]), $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }
}
