<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\TechStack;

use CurlySanders\JobApplicationTracker\Domain\Shared\NormalizesStrings;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tech_stacks')]
#[ORM\UniqueConstraint(name: 'UNIQ_TECH_STACKS_SLUG', fields: ['slug'])]
#[ORM\Index(name: 'IDX_TECH_STACKS_CATEGORY', fields: ['category'])]
final class TechStack
{
    use NormalizesStrings;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $slug;

    #[ORM\Column(length: 255)]
    private string $category;

    public function __construct(string $name, string $category)
    {
        $this->name = self::required($name, 'A technology name is required.');
        $this->slug = self::slug($this->name);
        $this->category = self::required($category, 'A technology category is required.');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getCategory(): string
    {
        return $this->category;
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
