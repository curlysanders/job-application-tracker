<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile;

use CurlySanders\JobApplicationTracker\Domain\User\User;

interface ResumeUploaderService
{
    public function uploadResume(User $user, ResumeUpload $upload): UploadedResume;

    /** @return resource */
    public function readResume(string $storagePath): mixed;

    public function deleteResume(string $storagePath): void;
}
