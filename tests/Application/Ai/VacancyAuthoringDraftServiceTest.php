<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftRepository;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftService;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyAuthoringDraft;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class VacancyAuthoringDraftServiceTest extends TestCase
{
    public function testMalformedDecryptedDraftsAreRejected(): void
    {
        $owner = new User();
        $draft = new VacancyAuthoringDraft($owner, 'encrypted', new \DateTimeImmutable('+1 day'));
        $repository = self::createStub(VacancyAuthoringDraftRepository::class);
        $repository->method('findOwnedBy')->willReturn($draft);

        foreach (['[]', '{"1":"value"}', '{"title":42}', '{"existingTags":42}'] as $plaintext) {
            $cipher = self::createStub(AnalysisCipher::class);
            $cipher->method('decrypt')->willReturn($plaintext);
            try {
                new VacancyAuthoringDraftService($repository, $cipher)->dataFor($draft->id->toRfc4122(), $owner);
                self::fail('Malformed draft content was accepted.');
            } catch (\LogicException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
