<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftCodec;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftRepository;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyAuthoringDraftService;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyAuthoringDraft;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class VacancyAuthoringDraftServiceTest extends TestCase
{
    public function testCodecReadsExistingDraftsAndRoundTripsAllFields(): void
    {
        $codec = new VacancyAuthoringDraftCodec();
        $legacy = json_decode('{"companyId":"company-id","recruiterId":"recruiter-id","proposedCompanyName":"Acme","proposedRecruiterAgencyName":"Recruiter","title":"Developer","location":"Delft","contractType":"PERMANENT","datePosted":"2026-11-01","deadline":"2026-12-01","fullText":"Vacancy","requirements":"PHP","responsibilities":"Build","preferredQualifications":"Symfony","aboutJob":"Role","aboutCompany":"Company","compensationBenefits":"Pension","minimumSalary":"4500","maximumSalary":"6000","currencyCode":"EUR","workMode":"HYBRID","hybridDetails":"Two days","existingTags":"php,symfony"}', true, 512, JSON_THROW_ON_ERROR);
        $draft = $codec->decode($legacy);

        self::assertSame('Developer', $draft->title);
        self::assertSame('PERMANENT', $draft->contractType?->value);
        self::assertSame('HYBRID', $draft->workMode?->value);
        self::assertSame('2026-11-01', $draft->datePosted?->format('Y-m-d'));
        self::assertSame('EUR', $draft->currencyCode);
        self::assertSame($legacy, $codec->encode($draft));
        self::assertEquals($draft, $codec->decode($codec->encode($draft)));
        self::assertSame('EUR', $codec->decode(['title' => 'Legacy'])->currencyCode);
    }

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
                new VacancyAuthoringDraftService($repository, $cipher, new VacancyAuthoringDraftCodec())->dataFor($draft->id->toRfc4122(), $owner);
                self::fail('Malformed draft content was accepted.');
            } catch (\LogicException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
