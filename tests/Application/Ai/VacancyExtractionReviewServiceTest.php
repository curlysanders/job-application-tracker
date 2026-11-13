<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use CurlySanders\JobApplicationTracker\Application\Ai\ReviewProposal;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionReview;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionReviewService;
use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\TechStack\PaginatedResults;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisResult;
use CurlySanders\JobApplicationTracker\Domain\Company\Company;
use CurlySanders\JobApplicationTracker\Domain\Recruiter\Recruiter;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class VacancyExtractionReviewServiceTest extends TestCase
{
    public function testReviewValidatesGroundedDatesSalaryCurrencyAndWorkMode(): void
    {
        $request = new AnalysisRequest(new User(), AnalysisKind::VacancyExtraction, 'input', str_repeat('a', 64), 'fake', 'fake', new \DateTimeImmutable('+1 hour'));
        $request->complete(new AnalysisResult($request, 'result', str_repeat('b', 64)));
        $cipher = new class implements AnalysisCipher {
            public function encrypt(string $plaintext): string
            {
                return $plaintext;
            }

            public function fingerprint(string $plaintext): string
            {
                return str_repeat('a', 64);
            }

            public function decrypt(string $ciphertext): string
            {
                return 'input' === $ciphertext
                    ? '{"vacancyText":"2026-12-01 2026-02-30 € 4500 0 hybrid"}'
                    : '{"proposals":[{"field":"datePosted","value":"2026-12-01","excerpt":"2026-12-01","confidence":1},{"field":"deadline","value":"2026-02-30","excerpt":"2026-02-30","confidence":1},{"field":"currencyCode","value":"EUR","excerpt":"€","confidence":1},{"field":"minimumSalary","value":"4500","excerpt":"4500","confidence":1},{"field":"maximumSalary","value":"0","excerpt":"0","confidence":1},{"field":"workMode","value":"HYBRID","excerpt":"hybrid","confidence":1}]}';
            }
        };
        $service = new VacancyExtractionReviewService($cipher, self::createStub(TechStackRepository::class), self::createStub(CompanyRepository::class), self::createStub(RecruiterRepository::class));
        $review = $service->review($request);

        self::assertArrayHasKey('datePosted', $review->fields);
        self::assertArrayHasKey('currencyCode', $review->fields);
        self::assertArrayHasKey('minimumSalary', $review->fields);
        self::assertArrayHasKey('workMode', $review->fields);
        self::assertArrayNotHasKey('deadline', $review->fields);
        self::assertArrayNotHasKey('maximumSalary', $review->fields);
    }

    public function testOrganizationCandidatesAreMatchedOnlyWithinTheOwnersRecords(): void
    {
        $owner = new User();
        $company = new Company($owner, 'Acme', null, null);
        $recruiter = new Recruiter($owner, 'Talent Partners', null);
        $companies = $this->createMock(CompanyRepository::class);
        $companies->expects(self::once())->method('searchOwnedBy')->with($owner->id->toRfc4122(), 'Acme')->willReturn([$company]);
        $recruiters = $this->createMock(RecruiterRepository::class);
        $recruiters->expects(self::once())->method('searchOwnedBy')->with($owner->id->toRfc4122(), 'Talent Partners')->willReturn([$recruiter]);
        $service = new VacancyExtractionReviewService(self::createStub(AnalysisCipher::class), self::createStub(TechStackRepository::class), $companies, $recruiters);
        $review = new VacancyExtractionReview('Acme Talent Partners', [], [], [new ReviewProposal('a', 'companyName', 'Acme', 'Acme', 1.0)], [new ReviewProposal('b', 'recruiterAgencyName', 'Talent Partners', 'Talent Partners', 1.0)]);
        $draft = $service->vacancyData($review, [], []);

        $service->applyOrganizationCandidates($draft, $review, $owner->id->toRfc4122());

        self::assertSame($company->id->toRfc4122(), $draft->companyId);
        self::assertSame($recruiter->id->toRfc4122(), $draft->recruiterId);
    }

    public function testSelectedReviewValuesPopulateTheDraft(): void
    {
        $values = [
            'title' => 'Engineer', 'location' => 'Delft', 'contractType' => 'PERMANENT', 'workMode' => 'HYBRID',
            'datePosted' => '2026-11-01', 'deadline' => '2026-12-01', 'minimumSalary' => '4000', 'maximumSalary' => '5000',
            'currencyCode' => 'EUR', 'hybridDetails' => 'Two days', 'requirements' => 'PHP', 'responsibilities' => 'Build',
            'preferredQualifications' => 'Symfony', 'aboutJob' => 'Development', 'aboutCompany' => 'A company', 'compensationBenefits' => 'Pension',
        ];
        $fields = [];
        $selected = [];
        foreach ($values as $field => $value) {
            $fields[$field] = [new ReviewProposal($field, $field, $value, $value, 1.0)];
            $selected[$field] = $field;
        }
        $service = new VacancyExtractionReviewService(self::createStub(AnalysisCipher::class), self::createStub(TechStackRepository::class), self::createStub(CompanyRepository::class), self::createStub(RecruiterRepository::class));
        $draft = $service->vacancyData(new VacancyExtractionReview('Original text', $fields, [], [], []), $selected, []);

        self::assertSame('Original text', $draft->fullText);
        self::assertSame('Delft', $draft->location);
        self::assertSame('HYBRID', $draft->workMode?->value);
        self::assertSame('2026-12-01', $draft->deadline?->format('Y-m-d'));
        self::assertSame('Pension', $draft->compensationBenefits);
    }

    public function testItOffersOnlyGroundedConfidentAndCompatibleProposals(): void
    {
        $request = new AnalysisRequest(new User(), AnalysisKind::VacancyExtraction, 'input', str_repeat('a', 64), 'fake', 'fake', new \DateTimeImmutable('+1 hour'));
        $request->complete(new AnalysisResult($request, 'result', str_repeat('b', 64)));
        $cipher = new class implements AnalysisCipher {
            public function encrypt(string $plaintext): string
            {
                return $plaintext;
            }

            public function decrypt(string $ciphertext): string
            {
                return 'input' === $ciphertext
                    ? '{"vacancyText":"Senior PHP developer in Rotterdam. Permanent, hybrid role. PHP is required."}'
                    : '{"proposals":[{"field":"title","value":"Senior PHP developer","excerpt":"Senior PHP developer","confidence":0.9},{"field":"location","value":"Rotterdam","excerpt":"Rotterdam","confidence":0.8},{"field":"contractType","value":"PERMANENT","excerpt":"Permanent","confidence":0.9},{"field":"workMode","value":"REMOTE","excerpt":"hybrid role","confidence":0.9},{"field":"currencyCode","value":"ZZZ","excerpt":"PHP","confidence":0.9},{"field":"requirements","value":"Invented","excerpt":"not present","confidence":0.99}],"techStacks":[{"name":"PHP","excerpt":"PHP is required","confidence":0.9},{"name":"Unknown","excerpt":"PHP is required","confidence":0.9}]}';
            }

            public function fingerprint(string $plaintext): string
            {
                return str_repeat('c', 64);
            }
        };
        $repository = new class implements TechStackRepository {
            public function findBySlug(string $slug): ?TechStack
            {
                return 'php' === $slug ? new TechStack('PHP', 'Backend') : null;
            }

            public function search(
                string $query,
                int $page = 1,
            ): PaginatedResults {
                throw new \LogicException();
            }

            public function categories(
                string $query,
                int $page = 1,
            ): PaginatedResults {
                throw new \LogicException();
            }

            public function save(TechStack $techStack): void
            {
            }
        };

        $service = new VacancyExtractionReviewService($cipher, $repository, self::createStub(CompanyRepository::class), self::createStub(RecruiterRepository::class));
        $review = $service->review($request);

        self::assertCount(1, $review->fields['title']);
        self::assertCount(1, $review->fields['location']);
        self::assertCount(1, $review->fields['contractType']);
        self::assertArrayNotHasKey('workMode', $review->fields);
        self::assertArrayNotHasKey('currencyCode', $review->fields);
        self::assertArrayNotHasKey('requirements', $review->fields);
        self::assertCount(1, $review->techStacks);

        $data = $service->vacancyData($review, [
            'title' => $review->fields['title'][0]->key,
            'contractType' => $review->fields['contractType'][0]->key,
        ], [$review->techStacks[0]->key]);

        self::assertSame('Senior PHP developer in Rotterdam. Permanent, hybrid role. PHP is required.', $data->fullText);
        self::assertSame('Senior PHP developer', $data->title);
        self::assertSame('PERMANENT', $data->contractType?->value);
        self::assertSame('php', $data->existingTags);
    }

    public function testItRejectsForgedProposalSelections(): void
    {
        $review = new VacancyExtractionReview('text', [], [], [], []);
        $service = new VacancyExtractionReviewService(self::createStub(AnalysisCipher::class), self::createStub(TechStackRepository::class), self::createStub(CompanyRepository::class), self::createStub(RecruiterRepository::class));

        $this->expectException(\InvalidArgumentException::class);
        $service->vacancyData($review, ['title' => 'forged'], []);
    }

    public function testItRejectsForgedTechnologySelections(): void
    {
        $review = new VacancyExtractionReview('text', [], [], [], []);
        $service = new VacancyExtractionReviewService(self::createStub(AnalysisCipher::class), self::createStub(TechStackRepository::class), self::createStub(CompanyRepository::class), self::createStub(RecruiterRepository::class));

        $this->expectException(\InvalidArgumentException::class);
        $service->vacancyData($review, [], ['forged']);
    }

    public function testItAcceptsCanonicalEnumsFromDutchEvidenceAndRemovesDuplicateRecruiterEvidence(): void
    {
        $request = new AnalysisRequest(new User(), AnalysisKind::VacancyExtraction, 'input', str_repeat('a', 64), 'local', 'qwen3:1.7b', new \DateTimeImmutable('+1 hour'));
        $request->complete(new AnalysisResult($request, 'result', str_repeat('b', 64)));
        $cipher = new class implements AnalysisCipher {
            public function encrypt(string $plaintext): string
            {
                return $plaintext;
            }

            public function decrypt(string $ciphertext): string
            {
                return 'input' === $ciphertext
                    ? '{"vacancyText":"Acme biedt een vast dienstverband met hybride werken."}'
                    : '{"proposals":[{"field":"companyName","value":"Acme","excerpt":"Acme","confidence":1},{"field":"recruiterAgencyName","value":" acme ","excerpt":"Acme","confidence":1},{"field":"contractType","value":"PERMANENT","excerpt":"vast dienstverband","confidence":1},{"field":"workMode","value":"HYBRID","excerpt":"hybride werken","confidence":1}],"techStacks":[]}';
            }

            public function fingerprint(string $plaintext): string
            {
                return str_repeat('c', 64);
            }
        };
        $service = new VacancyExtractionReviewService($cipher, self::createStub(TechStackRepository::class), self::createStub(CompanyRepository::class), self::createStub(RecruiterRepository::class));

        $review = $service->review($request);

        self::assertCount(1, $review->companyEvidence);
        self::assertCount(0, $review->recruiterEvidence);
        self::assertSame('PERMANENT', $review->fields['contractType'][0]->value);
        self::assertSame('HYBRID', $review->fields['workMode'][0]->value);
    }

    public function testItKeepsVerifiedUnicodeSourceSpansForHighlightedReviewEvidence(): void
    {
        $request = new AnalysisRequest(new User(), AnalysisKind::VacancyExtraction, 'input', str_repeat('a', 64), 'gliner', 'fastino/gliner2.5-multi-v1', new \DateTimeImmutable('+1 hour'));
        $request->complete(new AnalysisResult($request, 'result', str_repeat('b', 64)));
        $cipher = new class implements AnalysisCipher {
            public function encrypt(string $plaintext): string
            {
                return $plaintext;
            }

            public function decrypt(string $ciphertext): string
            {
                return 'input' === $ciphertext
                    ? '{"vacancyText":"München zoekt een PHP developer."}'
                    : '{"proposals":[{"field":"title","value":"PHP developer","excerpt":"PHP developer","confidence":0.95,"sourceStart":18,"sourceEnd":31}],"techStacks":[]}';
            }

            public function fingerprint(string $plaintext): string
            {
                return str_repeat('c', 64);
            }
        };
        $service = new VacancyExtractionReviewService($cipher, self::createStub(TechStackRepository::class), self::createStub(CompanyRepository::class), self::createStub(RecruiterRepository::class));

        $proposal = $service->review($request)->fields['title'][0];

        self::assertSame(18, $proposal->sourceStart);
        self::assertSame('München zoekt een ', $proposal->evidenceBefore);
        self::assertSame('PHP developer', $proposal->evidenceText);
    }
}
