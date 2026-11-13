<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionReviewService;
use CurlySanders\JobApplicationTracker\Application\Company\CompanyRepository;
use CurlySanders\JobApplicationTracker\Application\Recruiter\RecruiterRepository;
use CurlySanders\JobApplicationTracker\Application\TechStack\PaginatedResults;
use CurlySanders\JobApplicationTracker\Application\TechStack\TechStackRepository;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisResult;
use CurlySanders\JobApplicationTracker\Domain\TechStack\TechStack;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class VacancyExtractionReviewServiceTest extends TestCase
{
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
        $review = new \CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionReview('text', [], [], [], []);
        $service = new VacancyExtractionReviewService(self::createStub(AnalysisCipher::class), self::createStub(TechStackRepository::class), self::createStub(CompanyRepository::class), self::createStub(RecruiterRepository::class));

        $this->expectException(\InvalidArgumentException::class);
        $service->vacancyData($review, ['title' => 'forged'], []);
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
