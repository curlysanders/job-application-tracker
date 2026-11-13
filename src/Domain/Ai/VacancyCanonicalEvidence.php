<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Domain\Ai;

/** Canonical evidence rules for model-independent vacancy proposals. */
final class VacancyCanonicalEvidence
{
    /** @var array<string, array<string, list<string>>> */
    public const array PHRASES = [
        'contractType' => [
            'PERMANENT' => ['permanent', 'vast', 'onbepaalde tijd'],
            'FIXED_TERM' => ['fixed term', 'tijdelijk', 'bepaalde tijd'],
            'CONTRACT' => ['contract', 'overeenkomst'],
            'FREELANCE' => ['freelance', 'zzp'],
            'INTERNSHIP' => ['internship', 'stage'],
            'PART_TIME' => ['part-time', 'parttime', 'deeltijd'],
        ],
        'workMode' => [
            'REMOTE' => ['remote', 'op afstand'],
            'HYBRID' => ['hybrid', 'hybride', 'partly home', 'deels thuis', 'remote days'],
            'ONSITE' => ['onsite', 'on-site', 'op locatie', 'op kantoor', 'office'],
        ],
    ];

    public static function isGrounded(
        string $field,
        string $value,
        string $excerpt,
    ): bool {
        $excerpt = mb_strtolower($excerpt);
        $patterns = match ($field) {
            'contractType' => [
                'PERMANENT' => '/\b(permanent|vast(?:e)?(?: dienstverband)?|onbepaalde tijd)\b/u',
                'FIXED_TERM' => '/\b(fixed[ -]?term|tijdelijk(?:e)?|bepaalde tijd)\b/u',
                'CONTRACT' => '/\b(contract|overeenkomst)\b/u',
                'FREELANCE' => '/\b(freelance|zzp)\b/u',
                'INTERNSHIP' => '/\b(internship|stage)\b/u',
                'PART_TIME' => '/\b(part[ -]?time|deeltijd)\b/u',
            ],
            'workMode' => [
                'REMOTE' => '/\b(remote|op afstand)\b/u',
                'HYBRID' => '/\b(hybrid|hybride|partly home|deels thuis|remote days)\b/u',
                'ONSITE' => '/\b(onsite|on[ -]?site|op locatie|op kantoor|office)\b/u',
            ],
            default => [],
        };

        return isset($patterns[$value]) && 1 === preg_match($patterns[$value], $excerpt);
    }
}
