<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyDraftData;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyAuthoringDraft;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\ContractType;
use CurlySanders\JobApplicationTracker\Domain\Vacancy\WorkMode;

final readonly class VacancyAuthoringDraftService
{
    public function __construct(
        private VacancyAuthoringDraftRepository $drafts,
        private AnalysisCipher $cipher,
    ) {
    }

    public function create(
        User $user,
        VacancyDraftData $data,
    ): VacancyAuthoringDraft {
        $plaintext = json_encode($this->encode($data), JSON_THROW_ON_ERROR);
        $draft = new VacancyAuthoringDraft($user, $this->cipher->encrypt($plaintext), new \DateTimeImmutable('+7 days'));
        $this->drafts->save($draft);

        return $draft;
    }

    public function dataFor(
        string $draftId,
        User $user,
    ): ?VacancyDraftData {
        $draft = $this->drafts->findOwnedBy($draftId, $user->id->toRfc4122());
        if (null === $draft || $draft->isExpired(new \DateTimeImmutable())) {
            return null;
        }

        $decoded = json_decode($this->cipher->decrypt($draft->ciphertext), true, 512, JSON_THROW_ON_ERROR);

        return $this->decode($this->object($decoded));
    }

    public function consume(
        string $draftId,
        User $user,
    ): void {
        $draft = $this->drafts->findOwnedBy($draftId, $user->id->toRfc4122());
        if (null !== $draft) {
            $this->drafts->delete($draft);
        }
    }

    /** @return array<string, mixed> */
    private function encode(VacancyDraftData $data): array
    {
        return [
            'title' => $data->title, 'location' => $data->location, 'contractType' => $data->contractType?->value,
            'datePosted' => $data->datePosted?->format('Y-m-d'), 'deadline' => $data->deadline?->format('Y-m-d'),
            'fullText' => $data->fullText, 'requirements' => $data->requirements, 'responsibilities' => $data->responsibilities,
            'preferredQualifications' => $data->preferredQualifications, 'aboutJob' => $data->aboutJob,
            'aboutCompany' => $data->aboutCompany, 'compensationBenefits' => $data->compensationBenefits,
            'minimumSalary' => $data->minimumSalary, 'maximumSalary' => $data->maximumSalary, 'currencyCode' => $data->currencyCode,
            'workMode' => $data->workMode?->value, 'hybridDetails' => $data->hybridDetails,
            'existingTags' => $data->existingTags,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function decode(array $payload): VacancyDraftData
    {
        $data = new VacancyDraftData();
        foreach (['title', 'location', 'fullText', 'requirements', 'responsibilities', 'preferredQualifications', 'aboutJob', 'aboutCompany', 'compensationBenefits', 'minimumSalary', 'maximumSalary', 'hybridDetails'] as $field) {
            $value = $payload[$field] ?? null;
            if (null !== $value && !is_string($value)) {
                throw new \LogicException('A vacancy authoring draft contains an invalid text field.');
            }
            $data->{$field} = $value;
        }
        $data->currencyCode = is_string($payload['currencyCode'] ?? null) ? $payload['currencyCode'] : 'EUR';
        $data->contractType = is_string($payload['contractType'] ?? null) ? ContractType::tryFrom($payload['contractType']) : null;
        $data->workMode = is_string($payload['workMode'] ?? null) ? WorkMode::tryFrom($payload['workMode']) : null;
        foreach (['datePosted', 'deadline'] as $field) {
            $value = $payload[$field] ?? null;
            $data->{$field} = is_string($value) ? new \DateTimeImmutable($value) : null;
        }
        $existingTags = $payload['existingTags'] ?? null;
        if (null !== $existingTags && !is_string($existingTags)) {
            throw new \LogicException('A vacancy authoring draft contains invalid technology tags.');
        }
        $data->existingTags = $existingTags;

        return $data;
    }

    /** @return array<string, mixed> */
    private function object(mixed $value): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new \LogicException('A vacancy authoring draft must contain a JSON object.');
        }
        $object = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new \LogicException('A vacancy authoring draft must use string keys.');
            }
            $object[$key] = $item;
        }

        return $object;
    }
}
