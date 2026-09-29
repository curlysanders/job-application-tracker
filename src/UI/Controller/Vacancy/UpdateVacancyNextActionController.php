<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\UpdateVacancyNextAction;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class UpdateVacancyNextActionController
{
    public function __construct(
        private Security $security,
        private VacancyRepository $vacancies,
        private CommandBus $commandBus,
        private CsrfTokenManagerInterface $csrf,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/app/vacancies/{id}/next-action', name: 'app_vacancy_update_next_action', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function __invoke(int $id, Request $request): RedirectResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Vacancy reminder updates require an authenticated user.');
        }
        $userId = $user->getId() ?? throw new \LogicException('Vacancy reminder updates require a persisted user.');
        $this->vacancies->findOwnedBy($id, $userId) ?? throw new NotFoundHttpException('Vacancy not found.');
        if (!$this->csrf->isTokenValid(new CsrfToken(sprintf('vacancy_next_action_%d', $id), $request->request->getString('_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $clear = '1' === $request->request->getString('clear');
        $title = $clear ? '' : $request->request->getString('title');
        $atValue = $clear ? '' : $request->request->getString('at');
        if ('' === $atValue && '' !== trim($title)) {
            return $this->redirectWithWarning($request, $id, 'A next action title requires a date and time.');
        }
        try {
            $at = '' === $atValue ? null : new \DateTimeImmutable($atValue);
        } catch (\Exception) {
            return $this->redirectWithWarning($request, $id, 'Enter a valid next action date and time.');
        }

        $this->commandBus->dispatch(new UpdateVacancyNextAction($userId, $id, $title, $at));

        return $this->redirectWithWarning($request, $id, null === $at ? 'Next action cleared.' : 'Next action updated.', 'success');
    }

    private function redirectWithWarning(Request $request, int $id, string $message, string $type = 'warning'): RedirectResponse
    {
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Vacancy reminder updates require a flash-aware session.');
        }
        $session->getFlashBag()->add($type, $message);

        return new RedirectResponse($this->urls->generate('app_vacancy_show', ['id' => $id]));
    }
}
