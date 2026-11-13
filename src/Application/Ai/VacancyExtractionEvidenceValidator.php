<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyCanonicalEvidence;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyFieldValue;

final class VacancyExtractionEvidenceValidator
{
    private const float MINIMUM_CONFIDENCE = 0.80;

    /** @param array<string, mixed> $proposal */
    public function proposal(
        array $proposal,
        string $vacancyText,
        string $defaultField = '',
    ): ?ReviewProposal {
        $field = $proposal['field'] ?? $defaultField;
        $value = $proposal['value'] ?? $proposal['name'] ?? null;
        $excerpt = $proposal['excerpt'] ?? null;
        $confidence = $proposal['confidence'] ?? null;
        $sourceStart = $proposal['sourceStart'] ?? null;
        $sourceEnd = $proposal['sourceEnd'] ?? null;
        if (!is_string($field) || (!is_string($value) && !is_int($value) && !is_float($value)) || !is_string($excerpt) || (!is_float($confidence) && !is_int($confidence)) || !$this->validSourceSpan($sourceStart, $sourceEnd, $vacancyText, $excerpt)) {
            return null;
        }
        $value = trim((string) $value);
        if ('' === $value || '' === trim($excerpt) || $confidence < self::MINIMUM_CONFIDENCE || $confidence > 1 || !str_contains($vacancyText, $excerpt) || !$this->valueIsGrounded($field, $value, $excerpt)) {
            return null;
        }

        $sourceStart = is_int($sourceStart) ? $sourceStart : null;
        $sourceEnd = is_int($sourceEnd) ? $sourceEnd : null;
        [$evidenceBefore, $evidenceText, $evidenceAfter] = $this->evidenceContext($vacancyText, $excerpt, $sourceStart, $sourceEnd);

        return new ReviewProposal(hash('sha256', $field."\0".$value."\0".$excerpt."\0".($sourceStart ?? '')), $field, $value, $excerpt, (float) $confidence, $sourceStart, $sourceEnd, $evidenceBefore, $evidenceText, $evidenceAfter);
    }

    private function valueIsGrounded(
        string $field,
        string $value,
        string $excerpt,
    ): bool {
        if ('contractType' === $field || 'workMode' === $field) {
            return VacancyCanonicalEvidence::isGrounded($field, $value, $excerpt);
        }
        if ('currencyCode' === $field) {
            return ('EUR' === $value && (str_contains($excerpt, '€') || str_contains(mb_strtolower($excerpt), 'eur')))
                || str_contains(mb_strtolower($excerpt), mb_strtolower($value));
        }
        $value = mb_strtolower(str_replace('_', ' ', $value));
        $excerpt = mb_strtolower($excerpt);

        return str_contains($excerpt, $value);
    }

    private function validSourceSpan(
        mixed $sourceStart,
        mixed $sourceEnd,
        string $vacancyText,
        string $excerpt,
    ): bool {
        if (null === $sourceStart && null === $sourceEnd) {
            return true;
        }
        if (!is_int($sourceStart) || !is_int($sourceEnd) || $sourceStart < 0 || $sourceEnd <= $sourceStart) {
            return false;
        }

        return $excerpt === mb_substr($vacancyText, $sourceStart, $sourceEnd - $sourceStart);
    }

    /** @return array{string, string, string} */
    private function evidenceContext(
        string $vacancyText,
        string $excerpt,
        mixed $sourceStart,
        mixed $sourceEnd,
    ): array {
        if (!is_int($sourceStart) || !is_int($sourceEnd)) {
            return ['', $excerpt, ''];
        }
        $contextStart = max(0, $sourceStart - 120);

        return [
            mb_substr($vacancyText, $contextStart, $sourceStart - $contextStart),
            mb_substr($vacancyText, $sourceStart, $sourceEnd - $sourceStart),
            mb_substr($vacancyText, $sourceEnd, 120),
        ];
    }

    public function isValidFieldValue(ReviewProposal $proposal): bool
    {
        try {
            VacancyFieldValue::assertValid($proposal->field, $proposal->value);
        } catch (\InvalidArgumentException) {
            return false;
        }

        return !in_array($proposal->field, ['title', 'location'], true) || mb_strlen($proposal->value) <= 255;
    }
}
