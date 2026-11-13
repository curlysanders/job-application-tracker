<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\TechStack\StandardTechStacks;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyDraftData;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisState;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\CurrentIsoCurrencies;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final readonly class VacancyExtractionReviewService
{
    private const float MINIMUM_CONFIDENCE = 0.80;

    /** @var list<string> */
    private const array FIELDS = ['title', 'location', 'contractType', 'datePosted', 'deadline', 'minimumSalary', 'maximumSalary', 'currencyCode', 'workMode', 'hybridDetails', 'requirements', 'responsibilities', 'preferredQualifications', 'aboutJob', 'aboutCompany', 'compensationBenefits'];

    public function __construct(
        private AnalysisCipher $cipher,
        private TechStackRepository $techStacks,
        private CompanyRepository $companies,
        private RecruiterRepository $recruiters,
    ) {
    }

    public function review(AnalysisRequest $request): VacancyExtractionReview
    {
        if (AnalysisState::Completed !== $request->state || null === $request->result) {
            throw new \LogicException('Only completed analysis requests can be reviewed.');
        }

        $input = $this->decodeObject($this->cipher->decrypt($request->inputCiphertext));
        $result = $this->decodeObject($this->cipher->decrypt($request->result->ciphertext));
        $vacancyText = $input['vacancyText'] ?? null;
        if (!is_string($vacancyText)) {
            throw new \LogicException('A vacancy extraction request must contain the pasted vacancy text.');
        }

        $fields = [];
        $companyEvidence = [];
        $recruiterEvidence = [];
        foreach ($this->list($result, 'proposals') as $proposal) {
            $reviewProposal = $this->proposal($proposal, $vacancyText);
            if (null === $reviewProposal) {
                continue;
            }
            if ('companyName' === $reviewProposal->field) {
                $companyEvidence[] = $reviewProposal;
            } elseif ('recruiterAgencyName' === $reviewProposal->field) {
                $recruiterEvidence[] = $reviewProposal;
            } elseif (in_array($reviewProposal->field, self::FIELDS, true) && $this->isValidFieldValue($reviewProposal)) {
                $fields[$reviewProposal->field] ??= [];
                $fields[$reviewProposal->field][] = $reviewProposal;
            }
        }

        $techStacks = [];
        foreach ($this->list($result, 'techStacks') as $suggestion) {
            $proposal = $this->proposal($suggestion, $vacancyText, 'techStack');
            if (null !== $proposal && null !== $this->canonicalTechStack($proposal->value)) {
                $techStacks[] = $proposal;
            }
        }

        $companyNames = array_map(fn (ReviewProposal $proposal): string => $this->normalizeOrganizationName($proposal->value), $companyEvidence);
        $recruiterEvidence = array_values(array_filter(
            $recruiterEvidence,
            fn (ReviewProposal $proposal): bool => !in_array($this->normalizeOrganizationName($proposal->value), $companyNames, true),
        ));

        return new VacancyExtractionReview($vacancyText, $fields, $techStacks, $companyEvidence, $recruiterEvidence);
    }

    /**
     * @param array<string, string> $selectedFields
     * @param list<string>          $selectedTechStacks
     */
    public function vacancyData(
        VacancyExtractionReview $review,
        array $selectedFields,
        array $selectedTechStacks,
    ): VacancyDraftData {
        $data = new VacancyDraftData();
        $data->fullText = $review->vacancyText;
        foreach ($selectedFields as $field => $key) {
            $proposal = $this->findFieldProposal($review, $field, $key);
            if (null === $proposal) {
                throw new \InvalidArgumentException('The selected extraction proposal is unavailable.');
            }
            $this->apply($data, $proposal);
        }

        $slugs = [];
        foreach (array_unique($selectedTechStacks) as $key) {
            foreach ($review->techStacks as $proposal) {
                if (hash_equals($proposal->key, $key)) {
                    $techStack = $this->canonicalTechStack($proposal->value);
                    if (null === $techStack) {
                        throw new \InvalidArgumentException('The selected technology is unavailable.');
                    }
                    $slugs[] = $techStack->slug;
                    continue 2;
                }
            }
            throw new \InvalidArgumentException('The selected technology proposal is unavailable.');
        }
        $data->existingTags = implode(',', array_unique($slugs));

        return $data;
    }

    public function applyOrganizationCandidates(
        VacancyDraftData $data,
        VacancyExtractionReview $review,
        string $userId,
    ): void {
        $company = $review->companyEvidence[0] ?? null;
        if (null !== $company) {
            $data->proposedCompanyName = $company->value;
            $matches = array_values(array_filter($this->companies->searchOwnedBy($userId, $company->value), static fn ($item): bool => mb_strtolower(trim($item->name)) === mb_strtolower(trim($company->value))));
            if (1 === count($matches)) {
                $data->companyId = $matches[0]->id->toRfc4122();
            }
        }
        $recruiter = $review->recruiterEvidence[0] ?? null;
        if (null !== $recruiter) {
            $data->proposedRecruiterAgencyName = $recruiter->value;
            $matches = array_values(array_filter($this->recruiters->searchOwnedBy($userId, $recruiter->value), static fn ($item): bool => mb_strtolower(trim($item->agencyName)) === mb_strtolower(trim($recruiter->value))));
            if (1 === count($matches)) {
                $data->recruiterId = $matches[0]->id->toRfc4122();
            }
        }
    }

    /** @param array<string, mixed> $proposal */
    private function proposal(
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
            return $this->canonicalValueIsGrounded($field, $value, $excerpt);
        }
        if ('currencyCode' === $field) {
            return ('EUR' === $value && (str_contains($excerpt, '€') || str_contains(mb_strtolower($excerpt), 'eur')))
                || str_contains(mb_strtolower($excerpt), mb_strtolower($value));
        }
        $value = mb_strtolower(str_replace('_', ' ', $value));
        $excerpt = mb_strtolower($excerpt);

        return str_contains($excerpt, $value);
    }

    private function canonicalValueIsGrounded(
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

    private function normalizeOrganizationName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)) ?? '');
    }

    private function isValidFieldValue(ReviewProposal $proposal): bool
    {
        return match ($proposal->field) {
            'title', 'location' => mb_strlen($proposal->value) <= 255,
            'contractType' => null !== ContractType::tryFrom($proposal->value),
            'workMode' => null !== WorkMode::tryFrom($proposal->value),
            'currencyCode' => in_array($proposal->value, array_map(static fn ($currency): string => $currency->code, CurrentIsoCurrencies::all()), true),
            'datePosted', 'deadline' => $this->isIsoDate($proposal->value),
            'minimumSalary', 'maximumSalary' => 1 === preg_match('/^(?!0+(?:\.0+)?$)\d+(?:\.\d{1,2})?$/', $proposal->value),
            default => true,
        };
    }

    private function canonicalTechStack(string $name): ?TechStack
    {
        try {
            $slug = TechStack::slug($name);
        } catch (\InvalidArgumentException) {
            return null;
        }
        $standardNames = array_merge(...array_values(StandardTechStacks::all()));
        if (!in_array($name, $standardNames, true)) {
            return null;
        }

        return $this->techStacks->findBySlug($slug);
    }

    private function isIsoDate(string $value): bool
    {
        if (false === \DateTimeImmutable::createFromFormat('!Y-m-d', $value)) {
            return false;
        }
        $errors = \DateTimeImmutable::getLastErrors();

        return false === $errors || (0 === $errors['warning_count'] && 0 === $errors['error_count']);
    }

    private function findFieldProposal(
        VacancyExtractionReview $review,
        string $field,
        string $key,
    ): ?ReviewProposal {
        foreach ($review->fields[$field] ?? [] as $proposal) {
            if (hash_equals($proposal->key, $key)) {
                return $proposal;
            }
        }

        return null;
    }

    private function apply(
        VacancyDraftData $data,
        ReviewProposal $proposal,
    ): void {
        match ($proposal->field) {
            'contractType' => $data->contractType = ContractType::from($proposal->value),
            'workMode' => $data->workMode = WorkMode::from($proposal->value),
            'datePosted' => $data->datePosted = new \DateTimeImmutable($proposal->value),
            'deadline' => $data->deadline = new \DateTimeImmutable($proposal->value),
            'title' => $data->title = $proposal->value,
            'location' => $data->location = $proposal->value,
            'minimumSalary' => $data->minimumSalary = $proposal->value,
            'maximumSalary' => $data->maximumSalary = $proposal->value,
            'currencyCode' => $data->currencyCode = $proposal->value,
            'hybridDetails' => $data->hybridDetails = $proposal->value,
            'requirements' => $data->requirements = $proposal->value,
            'responsibilities' => $data->responsibilities = $proposal->value,
            'preferredQualifications' => $data->preferredQualifications = $proposal->value,
            'aboutJob' => $data->aboutJob = $proposal->value,
            'aboutCompany' => $data->aboutCompany = $proposal->value,
            'compensationBenefits' => $data->compensationBenefits = $proposal->value,
            default => throw new \LogicException('Unsupported vacancy extraction field.'),
        };
    }

    /** @return array<string, mixed> */
    private function decodeObject(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $this->object($decoded);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<array<string, mixed>>
     */
    private function list(
        array $payload,
        string $key,
    ): array {
        $items = $payload[$key] ?? [];
        if (!is_array($items) || !array_is_list($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            try {
                $result[] = $this->object($item);
            } catch (\LogicException) {
            }
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new \LogicException('Analysis data must be a JSON object.');
        }
        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new \LogicException('Analysis data must use string keys.');
            }
            $object[$key] = $item;
        }

        return $object;
    }
}
