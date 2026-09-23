<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\TechStack;

use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use PHPUnit\Framework\TestCase;

final class TechStackTest extends TestCase
{
    public function testNormalizesNameCategoryAndSlug(): void
    {
        $techStack = new TechStack('  Node.js  ', '  Backend  ');

        self::assertSame('Node.js', $techStack->getName());
        self::assertSame('node-js', $techStack->getSlug());
        self::assertSame('Backend', $techStack->getCategory());
    }

    public function testRejectsBlankValuesAndNamesWithoutSlugCharacters(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TechStack('   ', 'Backend');
    }

    public function testRejectsABlankCategory(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new TechStack('PHP', ' ');
    }
}
