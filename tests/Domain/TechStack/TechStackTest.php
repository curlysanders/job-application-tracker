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

        self::assertSame('Node.js', $techStack->name);
        self::assertSame('node-js', $techStack->slug);
        self::assertSame('Backend', $techStack->category);
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
