<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\UserProfile\Command;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class UploadResume
{
    public function __construct(public int $userId, public UploadedFile $file)
    {
    }
}
