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
        if (!is_string($field) || (!is_string($value) && !is_int($value) && !is_float($value)) || !is_string($excerpt) || (!is_float($confidence) && !is_int($confidence))) {
            return null;
        }
        $value = trim((string) $value);
        if ('' === $value || '' === trim($excerpt) || $confidence < self::MINIMUM_CONFIDENCE || $confidence > 1 || !str_contains($vacancyText, $excerpt) || !$this->valueIsGrounded($value, $excerpt)) {
            return null;
        }

        return new ReviewProposal(hash('sha256', $field."\0".$value."\0".$excerpt), $field, $value, $excerpt, (float) $confidence);
    }

    private function valueIsGrounded(
        string $value,
        string $excerpt,
    ): bool {
        $value = mb_strtolower(str_replace('_', ' ', $value));
        $excerpt = mb_strtolower($excerpt);

        return str_contains($excerpt, $value);
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
