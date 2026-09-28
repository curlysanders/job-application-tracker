<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Application\Vacancy\Command\DeleteVacancy;
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

final readonly class DeleteVacancyController
{
    public function __construct(private Security $security, private VacancyRepository $vacancies, private CommandBus $commandBus, private CsrfTokenManagerInterface $csrf, private UrlGeneratorInterface $urls)
    {
    }

    #[Route('/app/vacancies/{id}/delete', name: 'app_vacancy_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function __invoke(int $id, Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Vacancy deletion requires an authenticated user.');
        }
        $userId = $user->getId() ?? throw new \LogicException('Vacancy deletion requires a persisted user.');
        $this->vacancies->findOwnedBy($id, $userId) ?? throw new NotFoundHttpException('Vacancy not found.');
        if (!$this->csrf->isTokenValid(new CsrfToken(sprintf('vacancy_delete_%d', $id), $request->request->getString('_token')))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
        if ('delete' !== $request->request->getString('confirm')) {
            throw new AccessDeniedHttpException('Deletion was not confirmed.');
        }

        $this->commandBus->dispatch(new DeleteVacancy($userId, $id));
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Vacancy deletion requires a flash-aware session.');
        }
        $session->getFlashBag()->add('success', 'Vacancy deleted.');

        $return = $request->request->getString('return');

        return new RedirectResponse('/app' === parse_url($return, PHP_URL_PATH) ? $return : $this->urls->generate('app_dashboard'));
    }
}
