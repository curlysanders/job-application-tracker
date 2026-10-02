<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Markdown\MarkdownRenderer;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\UpdateVacancyScratchpad;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\UI\Controller\AuthenticatedUserTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final readonly class UpdateVacancyScratchpadController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private Security $security,
        private VacancyRepository $vacancies,
        private CommandBus $commandBus,
        private MarkdownRenderer $markdown,
        private CsrfTokenManagerInterface $csrf,
    ) {
    }

    #[Route('/vacancies/{id}/scratchpad', name: 'app_vacancy_update_scratchpad', methods: ['POST'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $user = $this->requireAuthenticatedUser('Vacancy scratchpad updates require an authenticated user.');
        $userId = $user->getId()->toRfc4122();
        $this->vacancies->findOwnedBy($id, $userId) ?? throw new NotFoundHttpException('Vacancy not found.');
        if (!$this->csrf->isTokenValid(new CsrfToken(sprintf('vacancy_scratchpad_%s', $id), $request->request->getString('_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
        $vacancy = $this->commandBus->dispatch(new UpdateVacancyScratchpad($userId, $id, $request->request->getString('notes')));
        if (!$vacancy instanceof Vacancy) {
            throw new \LogicException('A scratchpad update must return its vacancy.');
        }
        $notes = $vacancy->getScratchpadNotes();

        return new JsonResponse([
            'preview' => null === $notes ? '' : $this->markdown->render($notes),
            'savedAt' => new \DateTimeImmutable()->format(DATE_ATOM),
        ]);
    }
}
