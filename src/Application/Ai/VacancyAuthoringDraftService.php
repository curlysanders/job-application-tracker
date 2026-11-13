<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Vacancy\VacancyDraftData;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyAuthoringDraft;
use CurlySanders\JobApplicationTracker\Domain\User\User;

final readonly class VacancyAuthoringDraftService
{
    public function __construct(
        private VacancyAuthoringDraftRepository $drafts,
        private AnalysisCipher $cipher,
        private VacancyAuthoringDraftCodec $codec,
    ) {
    }

    public function create(
        User $user,
        VacancyDraftData $data,
    ): VacancyAuthoringDraft {
        $plaintext = json_encode($this->codec->encode($data), JSON_THROW_ON_ERROR);
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

        return $this->codec->decode($decoded);
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
}
