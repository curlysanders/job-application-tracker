<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\TransitionVacancyStatus;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Exception\VacancyStatusTransitionNotAllowed;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class TransitionVacancyStatusController
{
    public function __construct(
        private Security $security,
        private VacancyRepository $vacancies,
        private CommandBus $commandBus,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/app/vacancies/{id}/status', name: 'app_vacancy_transition_status', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function __invoke(int $id, Request $request): Response
    {
        $user = $this->authenticatedUser();
        $userId = $user->getId() ?? throw new \LogicException('Vacancy status changes require a persisted user.');
        $this->vacancies->findOwnedBy($id, $userId) ?? throw new NotFoundHttpException('Vacancy not found.');
        $token = $request->request->getString('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(sprintf('vacancy_status_transition_%d', $id), $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        try {
            $this->commandBus->dispatch(new TransitionVacancyStatus($userId, $id, $request->request->getString('transition')));
            $this->flash($request, 'success', 'Vacancy status updated.');
        } catch (VacancyStatusTransitionNotAllowed) {
            $this->flash($request, 'warning', 'That status transition is no longer available.');
        }

        return new RedirectResponse($this->urls->generate('app_vacancy_edit', ['id' => $id]));
    }

    private function authenticatedUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Vacancy status changes require an authenticated user.');
        }

        return $user;
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Vacancy status changes require a flash-aware session.');
        }

        $session->getFlashBag()->add($type, $message);
    }
}
