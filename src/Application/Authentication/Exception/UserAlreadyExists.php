<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Authentication\Exception;

final class UserAlreadyExists extends \DomainException
{
    public function __construct()
    {
        parent::__construct('An account already exists for this email address.');
    }
}
