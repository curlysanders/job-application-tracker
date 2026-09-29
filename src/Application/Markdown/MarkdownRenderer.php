<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Markdown;

interface MarkdownRenderer
{
    public function render(string $markdown): string;
}
