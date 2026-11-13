<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\TechStackSuggestion;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use Symfony\Component\Validator\Validation;

final class VacancyCorpusEvaluator
{
    /** @param list<array{id: string, text: string, expected: array<string, string|list<string>>}> $cases
     * @return array{cases: int, valid: int, precision: float, recall: float, f1: float, criticalPrecision: float, criticalRecall: float, criticalF1: float, medianLatencyMs: float, p95LatencyMs: float, maxLatencyMs: float, passed: bool, criticalPassed: bool, errors: list<array{id: string, error: string}>}
     */
    public function evaluate(
        array $cases,
        VacancyExtractionProvider $provider,
    ): array {
        $score = ['truePositives' => 0, 'falsePositives' => 0, 'falseNegatives' => 0];
        $criticalScore = ['truePositives' => 0, 'falsePositives' => 0, 'falseNegatives' => 0];
        $valid = 0;
        $latencies = [];
        $errors = [];

        foreach ($cases as $case) {
            $startedAt = hrtime(true);
            try {
                $result = $provider->extract(new VacancyExtractionInput($case['text']));
                $latencies[] = (hrtime(true) - $startedAt) / 1_000_000;
                if (0 !== Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($result)->count()) {
                    $this->recordMissingExpected($case['expected'], $score);
                    $this->recordMissingExpected($case['expected'], $criticalScore);
                    continue;
                }
                ++$valid;
            } catch (\Throwable $exception) {
                $errors[] = ['id' => $case['id'], 'error' => $exception->getMessage()];
                $this->recordMissingExpected($case['expected'], $score);
                $this->recordMissingExpected($case['expected'], $criticalScore);
                continue;
            }

            $actual = $this->actual($result);
            foreach ($case['expected'] as $field => $expected) {
                $expectedValues = is_array($expected) ? $expected : [$expected];
                $actualValues = $actual[$field] ?? [];
                foreach ($expectedValues as $value) {
                    if (in_array($this->normalize($value), $actualValues, true)) {
                        ++$score['truePositives'];
                        ++$criticalScore['truePositives'];
                    } else {
                        ++$score['falseNegatives'];
                        ++$criticalScore['falseNegatives'];
                    }
                }
                foreach ($actualValues as $value) {
                    if (!in_array($value, array_map($this->normalize(...), $expectedValues), true)) {
                        ++$score['falsePositives'];
                        ++$criticalScore['falsePositives'];
                    }
                }
            }
            foreach ($actual as $field => $actualValues) {
                if (array_key_exists($field, $case['expected'])) {
                    continue;
                }
                $score['falsePositives'] += count($actualValues);
            }
        }

        ['precision' => $precision, 'recall' => $recall, 'f1' => $f1] = $this->metrics($score);
        ['precision' => $criticalPrecision, 'recall' => $criticalRecall, 'f1' => $criticalF1] = $this->metrics($criticalScore);
        sort($latencies, SORT_NUMERIC);
        $latencyCount = count($latencies);
        $medianLatencyMs = 0 === $latencyCount ? 0.0 : ($latencies[(int) floor(($latencyCount - 1) / 2)] + $latencies[(int) floor($latencyCount / 2)]) / 2;
        $p95LatencyMs = 0 === $latencyCount ? 0.0 : $latencies[(int) ceil($latencyCount * 0.95) - 1];
        $maxLatencyMs = 0 === $latencyCount ? 0.0 : $latencies[$latencyCount - 1];

        return ['cases' => count($cases), 'valid' => $valid, 'precision' => $precision, 'recall' => $recall, 'f1' => $f1, 'criticalPrecision' => $criticalPrecision, 'criticalRecall' => $criticalRecall, 'criticalF1' => $criticalF1, 'medianLatencyMs' => $medianLatencyMs, 'p95LatencyMs' => $p95LatencyMs, 'maxLatencyMs' => $maxLatencyMs, 'passed' => count($cases) === $valid && $f1 >= 0.90, 'criticalPassed' => count($cases) === $valid && $criticalF1 >= 0.90, 'errors' => $errors];
    }

    /** @param array<string, string|list<string>> $expected
     * @param array{truePositives: int, falsePositives: int, falseNegatives: int} $score
     */
    private function recordMissingExpected(
        array $expected,
        array &$score,
    ): void {
        foreach ($expected as $values) {
            $score['falseNegatives'] += is_array($values) ? count($values) : 1;
        }
    }

    /** @param array{truePositives: int, falsePositives: int, falseNegatives: int} $score
     * @return array{precision: float, recall: float, f1: float}
     */
    private function metrics(array $score): array
    {
        $precision = 0 === $score['truePositives'] + $score['falsePositives'] ? 0.0 : (float) ($score['truePositives'] / ($score['truePositives'] + $score['falsePositives']));
        $recall = 0 === $score['truePositives'] + $score['falseNegatives'] ? 0.0 : (float) ($score['truePositives'] / ($score['truePositives'] + $score['falseNegatives']));

        return ['precision' => $precision, 'recall' => $recall, 'f1' => 0.0 === $precision + $recall ? 0.0 : 2 * $precision * $recall / ($precision + $recall)];
    }

    /** @return array<string, list<string>> */
    private function actual(VacancyExtractionResult $result): array
    {
        $values = [];
        foreach ($result->proposals as $proposal) {
            $values[$proposal->field] ??= [];
            $values[$proposal->field][] = $this->normalize(is_array($proposal->value) ? implode(',', $proposal->value) : (string) $proposal->value);
        }
        $values['techStacks'] = array_map(fn (TechStackSuggestion $suggestion): string => $this->normalize($suggestion->name), $result->techStacks);

        return $values;
    }

    private function normalize(string $value): string
    {
        return mb_strtolower(trim($value));
    }
}
