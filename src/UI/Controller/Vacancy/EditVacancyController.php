<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Currency\ExchangeRateProvider;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyRepository;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\UI\Form\VacancyFormDataFactory;
use CurlySanders\JobApplicationTracker\UI\Form\VacancyType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class EditVacancyController
{
    public function __construct(private Security $security, private VacancyRepository $vacancies, private CommandBus $commandBus, private FormFactoryInterface $forms, private VacancyFormDataFactory $dataFactory, private ExchangeRateProvider $exchangeRates, private Environment $twig, private UrlGeneratorInterface $urls)
    {
    }

    #[Route('/app/vacancies/{id}/edit', name: 'app_vacancy_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function __invoke(int $id, Request $request): Response
    {
        $user = $this->authenticatedUser();
        $vacancy = $this->vacancies->findOwnedBy($id, $this->userId($user)) ?? throw new NotFoundHttpException('Vacancy not found.');
        $data = $this->dataFactory->fromVacancy($vacancy);
        $form = $this->forms->create(VacancyType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch($this->dataFactory->command($this->userId($user), $id, $data));

            return $this->redirect($request, $id);
        }

        $rates = $this->exchangeRates->latestRates();
        $rateValues = null === $rates ? [] : $rates->ratesPerEuro;

        $preferredSalary = $user->getPreferredSalary();

        return new Response($this->twig->render('vacancy/form.html.twig', ['form' => $form->createView(), 'pageTitle' => 'Edit vacancy', 'submitLabel' => 'Save vacancy', 'minimumPreferredSalary' => $preferredSalary->getMinimumDecimal(), 'minimumPreferredSalaryCurrency' => $preferredSalary->getCurrencyCode(), 'exchangeRates' => $rateValues]), $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    private function authenticatedUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Vacancy authoring requires an authenticated user.');
        }

        return $user;
    }

    private function userId(User $user): int
    {
        return $user->getId() ?? throw new \LogicException('Vacancy authoring requires a persisted user.');
    }

    private function redirect(Request $request, int $id): RedirectResponse
    {
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Vacancy authoring requires a flash-aware session.');
        }
        $session->getFlashBag()->add('success', 'Vacancy updated.');

        return new RedirectResponse($this->urls->generate('app_vacancy_edit', ['id' => $id]));
    }
}
