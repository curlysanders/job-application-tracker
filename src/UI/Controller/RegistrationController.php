<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\Authentication\Command\RegisterUser;
use CurlySanders\JobApplicationTracker\Application\Authentication\Exception\UserAlreadyExists;
use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\UI\Form\Model\RegistrationData;
use CurlySanders\JobApplicationTracker\UI\Form\RegistrationType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class RegistrationController
{
    public function __construct(
        private CommandBus $commandBus,
        private Security $security,
        private FormFactoryInterface $formFactory,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        if (null !== $this->security->getUser()) {
            return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
        }

        $registration = new RegistrationData();
        $form = $this->formFactory->create(RegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $user = $this->commandBus->dispatch(new RegisterUser($registration->email, $registration->plainPassword));
            } catch (UserAlreadyExists $exception) {
                $form->get('email')->addError(new FormError($exception->getMessage()));

                return $this->renderRegistrationForm($form, Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (!$user instanceof User) {
                throw new \LogicException('The register-user command must return a user.');
            }

            $session = $request->getSession();
            if (!$session instanceof FlashBagAwareSessionInterface) {
                throw new \LogicException('Account registration requires a flash-aware session.');
            }
            $session->getFlashBag()->add('success', 'Your account has been created.');

            return $this->security->login($user, 'form_login', 'main') ?? new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
        }

        return $this->renderRegistrationForm($form, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }

    /** @param FormInterface<RegistrationData> $form */
    private function renderRegistrationForm(FormInterface $form, int $status = Response::HTTP_OK): Response
    {
        return new Response($this->twig->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]), $status);
    }
}
