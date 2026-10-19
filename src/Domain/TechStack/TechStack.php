<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\TechStack;

use CurlySanders\JobApplicationTracker\Domain\Shared\Identifier;
use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsDomainEvents;
use CurlySanders\JobApplicationTracker\Domain\Shared\RecordsEvents;

final class TechStack implements RecordsEvents
{
    use NormalizesStrings;
    use RecordsDomainEvents;

    private(set) Identifier $id;

    private(set) string $name;

    private(set) string $slug;

    private(set) string $category;

    public function __construct(
        string $name,
        string $category,
    ) {
        $this->id = Identifier::v7();
        $this->name = self::required($name, 'A technology name is required.');
        $this->slug = self::slug($this->name);
        $this->category = self::required($category, 'A technology category is required.');
        $this->recordEvent(new TechStackCreated($this->id, ['name' => $this->name, 'slug' => $this->slug, 'category' => $this->category]));
    }

    public static function slug(string $name): string
    {
        $name = self::required($name, 'A technology name is required.');
        $slug = mb_strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);
        $slug = trim((string) $slug, '-');

        if ('' === $slug) {
            throw new \InvalidArgumentException('A technology name must contain letters or numbers.');
        }

        return $slug;
    }
}
