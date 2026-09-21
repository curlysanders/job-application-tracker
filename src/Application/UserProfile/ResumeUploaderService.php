<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile;

use CurlySanders\JobApplicationTracker\Domain\User\User;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface ResumeUploaderService
{
    public function uploadResume(User $user, UploadedFile $file): UploadedResume;

    /** @return resource */
    public function readResume(string $storagePath): mixed;

    public function deleteResume(string $storagePath): void;
}
