<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Vacancy;

final readonly class CurrencyOption
{
    public function __construct(public string $code, public string $name)
    {
        if (1 !== preg_match('/^[A-Z]{3}$/', $code) || '' === trim($name)) {
            throw new \InvalidArgumentException('A currency option requires an ISO 4217 code and a name.');
        }
    }
}
