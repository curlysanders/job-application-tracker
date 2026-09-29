<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\SetVacancyArchived;
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

final readonly class ArchiveVacancyController
{
    public function __construct(private Security $security, private VacancyRepository $vacancies, private CommandBus $commandBus, private CsrfTokenManagerInterface $csrf, private UrlGeneratorInterface $urls)
    {
    }

    #[Route('/vacancies/{id}/archive', name: 'app_vacancy_archive', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function __invoke(int $id, Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Vacancy archiving requires an authenticated user.');
        }
        $userId = $user->getId() ?? throw new \LogicException('Vacancy archiving requires a persisted user.');
        $this->vacancies->findOwnedBy($id, $userId) ?? throw new NotFoundHttpException('Vacancy not found.');
        if (!$this->csrf->isTokenValid(new CsrfToken(sprintf('vacancy_archive_%d', $id), $request->request->getString('_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $archived = '1' === $request->request->getString('archived');
        $this->commandBus->dispatch(new SetVacancyArchived($userId, $id, $archived));
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Vacancy archiving requires a flash-aware session.');
        }
        $session->getFlashBag()->add('success', $archived ? 'Vacancy archived.' : 'Vacancy restored.');

        return new RedirectResponse($this->returnUrl($request));
    }

    private function returnUrl(Request $request): string
    {
        $return = $request->request->getString('return');

        return '/' === parse_url($return, PHP_URL_PATH) ? $return : $this->urls->generate('app_dashboard');
    }
}
