<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile\Command;

use CurlySanders\JobApplicationTracker\Application\UserProfile\ResumeUpload;

final readonly class UploadResume
{
    public function __construct(public int $userId, public ResumeUpload $upload)
    {
    }
}
