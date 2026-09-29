<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Markdown\MarkdownRenderer;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyStatusHistoryRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Twig\Environment;

final readonly class VacancyDetailController
{
    public function __construct(
        private Security $security,
        private VacancyRepository $vacancies,
        private VacancyStatusHistoryRepository $statusHistory,
        private MarkdownRenderer $markdown,
        private Environment $twig,
        #[Autowire(service: 'state_machine.vacancy_status')]
        private WorkflowInterface $workflow,
    ) {
    }

    #[Route('/vacancies/{id}', name: 'app_vacancy_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Vacancy details require an authenticated user.');
        }
        $userId = $user->getId() ?? throw new \LogicException('Vacancy details require a persisted user.');
        $vacancy = $this->vacancies->findOwnedBy($id, $userId) ?? throw new NotFoundHttpException('Vacancy not found.');
        $notes = $vacancy->getScratchpadNotes();

        return new Response($this->twig->render('vacancy/detail.html.twig', [
            'vacancy' => $vacancy,
            'scratchpadPreview' => null === $notes ? null : $this->markdown->render($notes),
            'enabledStatusTransitions' => $this->workflow->getEnabledTransitions($vacancy),
            'statusHistory' => $this->statusHistory->findForVacancy($vacancy),
        ]));
    }
}
