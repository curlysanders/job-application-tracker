<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Markdown;

use CurlySanders\JobApplicationTracker\Application\Markdown\MarkdownRenderer;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MarkdownRenderer::class)]
final readonly class CommonMarkMarkdownRenderer implements MarkdownRenderer
{
    private GithubFlavoredMarkdownConverter $converter;

    public function __construct()
    {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function render(string $markdown): string
    {
        return (string) $this->converter->convert($markdown);
    }
}
