<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

final readonly class CurrencyOption
{
    public function __construct(public string $code, public string $name)
    {
    }
}
