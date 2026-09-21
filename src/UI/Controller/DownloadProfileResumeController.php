<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DownloadProfileResumeController
{
    public function __construct(
        private Security $security,
        private ResumeUploaderService $resumeUploader,
    ) {
    }

    #[Route('/app/profile/resume', name: 'app_profile_resume', methods: ['GET'])]
    public function __invoke(): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User || null === $user->getResumeStoragePath() || null === $user->getResumeOriginalFilename() || null === $user->getResumeMimeType()) {
            throw new NotFoundHttpException('No active resume was found.');
        }

        $stream = $this->resumeUploader->readResume($user->getResumeStoragePath());

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });
        $response->headers->set('Content-Type', $user->getResumeMimeType());
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition('attachment', $user->getResumeOriginalFilename()));

        return $response;
    }
}
