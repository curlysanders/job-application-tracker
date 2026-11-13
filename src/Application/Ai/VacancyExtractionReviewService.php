<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisState;
use CurlySanders\JobApplicationTracker\Domain\Ai\OrganizationName;

final readonly class VacancyExtractionReviewService
{
    /** @var list<string> */
    private const array FIELDS = ['title', 'location', 'contractType', 'datePosted', 'deadline', 'minimumSalary', 'maximumSalary', 'currencyCode', 'workMode', 'hybridDetails', 'requirements', 'responsibilities', 'preferredQualifications', 'aboutJob', 'aboutCompany', 'compensationBenefits'];

    public function __construct(
        private AnalysisCipher $cipher,
        private VacancyExtractionEvidenceValidator $evidence,
        private StandardTechnologyResolver $technologies,
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
            $reviewProposal = $this->evidence->proposal($proposal, $vacancyText);
            if (null === $reviewProposal) {
                continue;
            }
            if ('companyName' === $reviewProposal->field) {
                $companyEvidence[] = $reviewProposal;
            } elseif ('recruiterAgencyName' === $reviewProposal->field) {
                $recruiterEvidence[] = $reviewProposal;
            } elseif (in_array($reviewProposal->field, self::FIELDS, true) && $this->evidence->isValidFieldValue($reviewProposal)) {
                $fields[$reviewProposal->field] ??= [];
                $fields[$reviewProposal->field][] = $reviewProposal;
            }
        }

        $techStacks = [];
        foreach ($this->list($result, 'techStacks') as $suggestion) {
            $proposal = $this->evidence->proposal($suggestion, $vacancyText, 'techStack');
            if (null !== $proposal && null !== $this->technologies->find($proposal->value)) {
                $techStacks[] = $proposal;
            }
        }

        $companyNames = array_map(fn (ReviewProposal $proposal): string => OrganizationName::normalize($proposal->value), $companyEvidence);
        $recruiterEvidence = array_values(array_filter(
            $recruiterEvidence,
            fn (ReviewProposal $proposal): bool => !in_array(OrganizationName::normalize($proposal->value), $companyNames, true),
        ));

        return new VacancyExtractionReview($vacancyText, $fields, $techStacks, $companyEvidence, $recruiterEvidence);
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
