<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisRequestRepository;
use CurlySanders\JobApplicationTracker\Application\Ai\CandidateFitProvider;
use CurlySanders\JobApplicationTracker\Application\Ai\ProcessAnalysisRequest;
use CurlySanders\JobApplicationTracker\Application\Ai\VacancyExtractionProvider;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\CandidateFitReport;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionInput;
use CurlySanders\JobApplicationTracker\Domain\Ai\VacancyExtractionResult;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ProcessAnalysisRequestTest extends TestCase
{
    public const string FINGERPRINT = 'b8c244a13ba49b7f0e49157ddd40dbffb2d86eb17e107b24b8954ce61a2b7b17';

    public function testProcessesOnlyTheCurrentVersionAndStoresOneEncryptedResult(): void
    {
        $request = new AnalysisRequest(new User(), AnalysisKind::VacancyExtraction, 'ciphertext', self::FINGERPRINT, 'fake', 'fake', new \DateTimeImmutable('+24 hours'));
        $repository = new class($request) implements AnalysisRequestRepository {
            public bool $completed = false;

            public function __construct(private readonly AnalysisRequest $request)
            {
            }

            public function findOwnedBy(
                string $requestId,
                string $userId,
            ): ?AnalysisRequest {
                return null;
            }

            public function find(string $requestId): AnalysisRequest
            {
                return $this->request;
            }

            public function save(AnalysisRequest $request): void
            {
            }

            public function complete(
                string $requestId,
                string $inputFingerprint,
                string $resultCiphertext,
                string $resultFingerprint,
            ): bool {
                $this->completed = 'encrypted-result' === $resultCiphertext && ProcessAnalysisRequestTest::FINGERPRINT === $inputFingerprint && 1 === preg_match('/^[a-f0-9]{64}$/', $resultFingerprint);

                return $this->completed;
            }

            public function fail(
                string $requestId,
                string $inputFingerprint,
            ): void {
                throw new \LogicException('The provider should not fail.');
            }

            public function purgeExpired(\DateTimeImmutable $now): int
            {
                return 0;
            }
        };
        $cipher = new class implements AnalysisCipher {
            public function encrypt(string $plaintext): string
            {
                return 'encrypted-result';
            }

            public function decrypt(string $ciphertext): string
            {
                return '{"vacancyText":"Private vacancy text","language":"nl"}';
            }

            public function fingerprint(string $plaintext): string
            {
                return str_repeat('e', 64);
            }
        };
        $provider = new class implements VacancyExtractionProvider {
            public function extract(VacancyExtractionInput $input): VacancyExtractionResult
            {
                \PHPUnit\Framework\Assert::assertSame('Private vacancy text', $input->vacancyText);

                return new VacancyExtractionResult();
            }
        };

        new ProcessAnalysisRequest($repository, $cipher, $provider, new class implements CandidateFitProvider {
            public function assess(CandidateFitInput $input): CandidateFitReport
            {
                throw new \LogicException('Candidate fit must not run.');
            }
        }, new NullLogger())($request->id->toRfc4122(), self::FINGERPRINT);

        self::assertTrue($repository->completed);
    }

    public function testRecordsOnlyAGenericFailureWhenTheProviderThrows(): void
    {
        $request = new AnalysisRequest(new User(), AnalysisKind::VacancyExtraction, 'ciphertext', self::FINGERPRINT, 'fake', 'fake', new \DateTimeImmutable('+24 hours'));
        $repository = $this->createMock(AnalysisRequestRepository::class);
        $repository->expects(self::once())->method('find')->willReturn($request);
        $repository->expects(self::once())->method('fail')->with($request->id->toRfc4122(), self::FINGERPRINT);
        $repository->expects(self::never())->method('complete');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning')->with('AI analysis provider failed.', [
            'analysis_request_id' => $request->id->toRfc4122(),
            'input_fingerprint' => self::FINGERPRINT,
        ]);

        new ProcessAnalysisRequest($repository, new class implements AnalysisCipher {
            public function encrypt(string $plaintext): string
            {
                return '';
            }

            public function decrypt(string $ciphertext): string
            {
                return '{"vacancyText":"Private vacancy text"}';
            }

            public function fingerprint(string $plaintext): string
            {
                return '';
            }
        }, new class implements VacancyExtractionProvider {
            public function extract(VacancyExtractionInput $input): VacancyExtractionResult
            {
                throw new \RuntimeException('Provider saw: '.$input->vacancyText);
            }
        }, new class implements CandidateFitProvider {
            public function assess(CandidateFitInput $input): CandidateFitReport
            {
                throw new \LogicException('Candidate fit must not run.');
            }
        }, $logger)($request->id->toRfc4122(), self::FINGERPRINT);
    }

    public function testProcessesCandidateFitWithBothPrivateInputs(): void
    {
        $request = new AnalysisRequest(new User(), AnalysisKind::CandidateFit, 'ciphertext', self::FINGERPRINT, 'fake', 'fake', new \DateTimeImmutable('+24 hours'));
        $repository = $this->createMock(AnalysisRequestRepository::class);
        $repository->expects(self::once())->method('find')->with($request->id->toRfc4122())->willReturn($request);
        $repository->expects(self::once())->method('complete')->with(
            $request->id->toRfc4122(),
            self::FINGERPRINT,
            'encrypted-result',
            str_repeat('f', 64),
        )->willReturn(true);
        $repository->expects(self::never())->method('fail');

        new ProcessAnalysisRequest($repository, new class implements AnalysisCipher {
            public function encrypt(string $plaintext): string
            {
                return 'encrypted-result';
            }

            public function decrypt(string $ciphertext): string
            {
                return '{"vacancyText":"Private vacancy text","resumeText":"Private resume text"}';
            }

            public function fingerprint(string $plaintext): string
            {
                return str_repeat('f', 64);
            }
        }, self::createStub(VacancyExtractionProvider::class), new class implements CandidateFitProvider {
            public function assess(CandidateFitInput $input): CandidateFitReport
            {
                \PHPUnit\Framework\Assert::assertSame('Private vacancy text', $input->vacancyText);
                \PHPUnit\Framework\Assert::assertSame('Private resume text', $input->resumeText);

                return new CandidateFitReport();
            }
        }, new NullLogger())($request->id->toRfc4122(), self::FINGERPRINT);
    }

    public function testIgnoresAStaleDeliveryBeforeDecryptingTheInput(): void
    {
        $repository = $this->createMock(AnalysisRequestRepository::class);
        $repository->expects(self::once())->method('find')->with('missing-request')->willReturn(null);
        $repository->expects(self::never())->method('fail');
        $cipher = $this->createMock(AnalysisCipher::class);
        $cipher->expects(self::never())->method('decrypt');

        new ProcessAnalysisRequest($repository, $cipher, self::createStub(VacancyExtractionProvider::class), self::createStub(CandidateFitProvider::class), new NullLogger())('missing-request', self::FINGERPRINT);
    }
}
