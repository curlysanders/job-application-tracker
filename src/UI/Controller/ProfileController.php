<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\Shared\Bus\CommandBus;
use CurlySanders\JobApplicationTracker\Application\UserProfile\Command\UpdateUserPreferences;
use CurlySanders\JobApplicationTracker\Application\UserProfile\Command\UploadResume;
use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUpload;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\UI\Form\Model\ProfileSettingsData;
use CurlySanders\JobApplicationTracker\UI\Form\Model\ResumeUploadData;
use CurlySanders\JobApplicationTracker\UI\Form\ProfileSettingsType;
use CurlySanders\JobApplicationTracker\UI\Form\ResumeUploadType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final readonly class ProfileController
{
    public function __construct(
        private Security $security,
        private CommandBus $commandBus,
        private FormFactoryInterface $formFactory,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/app/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getId()) {
            throw new \LogicException('Profile settings require an authenticated user.');
        }

        $profile = new ProfileSettingsData();
        $profile->minimumPreferredSalary = $user->getMinimumPreferredSalary()?->toDecimal();
        $profile->maximumCommuteMinutes = $user->getMaximumCommuteMinutes();
        $profile->preferredTransportMode = $user->getPreferredTransportMode();

        $form = $this->formFactory->create(ProfileSettingsType::class, $profile);
        $form->handleRequest($request);

        $resumeUpload = new ResumeUploadData();
        $resumeForm = $this->formFactory->createNamed('resume_upload', ResumeUploadType::class, $resumeUpload);
        $resumeForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->commandBus->dispatch(new UpdateUserPreferences(
                $user->getId(),
                $profile->minimumPreferredSalary,
                $profile->maximumCommuteMinutes,
                $profile->preferredTransportMode,
            ));

            $session = $request->getSession();
            if (!$session instanceof FlashBagAwareSessionInterface) {
                throw new \LogicException('Profile updates require a flash-aware session.');
            }
            $session->getFlashBag()->add('success', 'Your profile settings have been updated.');

            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        if ($resumeForm->isSubmitted() && $resumeForm->isValid()) {
            if (null === $resumeUpload->resume) {
                throw new \LogicException('A valid resume upload must contain a file.');
            }

            $stream = fopen($resumeUpload->resume->getPathname(), 'rb');
            if (false === $stream) {
                throw new \RuntimeException('The uploaded resume could not be read.');
            }

            try {
                $this->commandBus->dispatch(new UploadResume($user->getId(), new ResumeUpload(
                    $stream,
                    $resumeUpload->resume->getClientOriginalName(),
                    $resumeUpload->resume->getMimeType() ?? '',
                    false === $resumeUpload->resume->getSize() ? 0 : $resumeUpload->resume->getSize(),
                )));
            } finally {
                fclose($stream);
            }

            $session = $request->getSession();
            if (!$session instanceof FlashBagAwareSessionInterface) {
                throw new \LogicException('Resume uploads require a flash-aware session.');
            }
            $session->getFlashBag()->add('success', 'Your active resume has been uploaded.');

            return new RedirectResponse($this->urlGenerator->generate('app_profile'));
        }

        return new Response($this->twig->render('profile/settings.html.twig', [
            'profileForm' => $form->createView(),
            'resumeForm' => $resumeForm->createView(),
            'user' => $user,
        ]), $form->isSubmitted() || $resumeForm->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK);
    }
}
