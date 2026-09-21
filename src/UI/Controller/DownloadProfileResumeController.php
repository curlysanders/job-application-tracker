<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Controller;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUploaderService;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use League\Flysystem\UnableToReadFile;
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
        if (!$user instanceof User) {
            throw new NotFoundHttpException('No active resume was found.');
        }

        $storagePath = $user->getResumeStoragePath();
        $originalFilename = $user->getResumeOriginalFilename();
        $mimeType = $user->getResumeMimeType();
        if (null === $storagePath || null === $originalFilename || null === $mimeType) {
            throw new NotFoundHttpException('No active resume was found.');
        }

        try {
            $stream = $this->resumeUploader->readResume($storagePath);
        } catch (UnableToReadFile) {
            throw new NotFoundHttpException('No active resume was found.');
        }

        if (!is_resource($stream)) {
            throw new \LogicException('A resume download must provide a readable stream.');
        }

        $response = new StreamedResponse(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition('attachment', $originalFilename));
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
