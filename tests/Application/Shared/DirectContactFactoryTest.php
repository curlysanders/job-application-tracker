<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Shared;

use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactFactory;
use CurlySanders\JobApplicationTracker\Application\Shared\DirectContactInput;
use PHPUnit\Framework\TestCase;

final class DirectContactFactoryTest extends TestCase
{
    public function testCreatesContactsFromApplicationInputs(): void
    {
        $contacts = DirectContactFactory::fromInputs([
            new DirectContactInput('Ada Lovelace', 'ada@example.com', '+31 6 12345678', 'https://www.linkedin.com/in/ada'),
        ]);

        self::assertCount(1, $contacts);
        self::assertSame('Ada Lovelace', $contacts[0]->getName());
        self::assertSame('ada@example.com', $contacts[0]->getEmail());
        self::assertSame('+31 6 12345678', $contacts[0]->getPhone());
        self::assertSame('https://www.linkedin.com/in/ada', $contacts[0]->getLinkedinUrl());
    }
}
