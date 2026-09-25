<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller\Vacancy;

use CurlySanders\JobApplicationTracker\Application\Currency\ExchangeRateProvider;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\Vacancy;
use CurlySanders\JobApplicationTracker\UI\Form\Model\VacancyData;
use CurlySanders\JobApplicationTracker\UI\Form\VacancyFormDataFactory;
use CurlySanders\JobApplicationTracker\UI\Form\VacancyType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class CreateVacancyController
{
    public function __construct(
        private Security $security,
        private CommandBus $commandBus,
        private FormFactoryInterface $forms,
        private VacancyFormDataFactory $dataFactory,
        private ExchangeRateProvider $exchangeRates,
        private Environment $twig,
        private UrlGeneratorInterface $urls,
    ) {
    }

    #[Route('/app/vacancies/new', name: 'app_vacancy_create', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->authenticatedUser();
        $data = new VacancyData();
        $form = $this->forms->create(VacancyType::class, $data);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vacancy = $this->commandBus->dispatch($this->dataFactory->command($this->userId($user), null, $data));
            if (!$vacancy instanceof Vacancy || null === $vacancy->getId()) {
                throw new \LogicException('A created vacancy must be persisted.');
            }

            return $this->redirect($request, $vacancy->getId(), 'Vacancy created.');
        }

        return $this->response($form->createView(), $user, 'Add vacancy', 'Create vacancy', $form->isSubmitted());
    }

    private function response(FormView $form, User $user, string $pageTitle, string $submitLabel, bool $submitted): Response
    {
        $rates = $this->exchangeRates->latestRates();
        $rateValues = null === $rates ? [] : $rates->ratesPerEuro;

        $preferredSalary = $user->getPreferredSalary();

        return new Response($this->twig->render('vacancy/form.html.twig', ['form' => $form, 'pageTitle' => $pageTitle, 'submitLabel' => $submitLabel, 'minimumPreferredSalary' => $preferredSalary->getMinimumDecimal(), 'minimumPreferredSalaryCurrency' => $preferredSalary->getCurrencyCode(), 'exchangeRates' => $rateValues]), $submitted ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
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

    private function redirect(Request $request, int $id, string $message): RedirectResponse
    {
        $session = $request->getSession();
        if (!$session instanceof FlashBagAwareSessionInterface) {
            throw new \LogicException('Vacancy authoring requires a flash-aware session.');
        }
        $session->getFlashBag()->add('success', $message);

        return new RedirectResponse($this->urls->generate('app_vacancy_edit', ['id' => $id]));
    }
}
