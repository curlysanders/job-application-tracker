<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\UI\Form\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ResumeUploadData
{
    public ?UploadedFile $resume = null;
}
