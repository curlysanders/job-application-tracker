<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Vacancy\Exception;

final class VacancyStatusTransitionNotAllowed extends \LogicException
{
    public function __construct(string $transition, \Throwable $previous)
    {
        parent::__construct(sprintf('The "%s" status transition is no longer available.', $transition), 0, $previous);
    }
}
