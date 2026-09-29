<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Markdown;

use CurlySanders\JobApplicationTracker\Infrastructure\Markdown\CommonMarkMarkdownRenderer;
use PHPUnit\Framework\TestCase;

final class CommonMarkMarkdownRendererTest extends TestCase
{
    public function testRendersMarkdownWithoutAllowingUnsafeHtmlOrLinks(): void
    {
        $html = new CommonMarkMarkdownRenderer()->render("# Prepare\n\n- [x] Review notes\n\n| Topic | Status |\n| --- | --- |\n| Salary | Ask |\n\n<script>alert('x')</script>\n\n[jump](javascript:alert(1))");

        self::assertStringContainsString('<h1>Prepare</h1>', $html);
        self::assertStringContainsString('type="checkbox"', $html);
        self::assertStringContainsString('<table>', $html);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('javascript:', $html);
    }
}
