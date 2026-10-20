<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Application\Ai;

use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisCipher;
use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisProviderMetadata;
use CurlySanders\JobApplicationTracker\Application\Ai\AnalysisRequestRepository;
use CurlySanders\JobApplicationTracker\Application\Ai\Command\QueueAnalysisRequest;
use CurlySanders\JobApplicationTracker\Application\Ai\Command\QueueAnalysisRequestHandler;
use CurlySanders\JobApplicationTracker\Application\Authentication\UserRepository;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class QueueAnalysisRequestHandlerTest extends TestCase
{
    private const string USER_ID = '018f8e3e-1234-7abc-8def-0123456789ab';

    public function testQueuesCandidateFitWithEncryptedInput(): void
    {
        $user = new User();
        $users = $this->createMock(UserRepository::class);
        $users->expects(self::once())->method('find')->with(self::USER_ID)->willReturn($user);
        $requests = $this->createMock(AnalysisRequestRepository::class);
        $requests->expects(self::once())->method('save')->with(self::isInstanceOf(AnalysisRequest::class));
        $cipher = $this->createMock(AnalysisCipher::class);
        $cipher->expects(self::once())->method('encrypt')->with(self::callback(static function (string $payload): bool {
            $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

            return ['vacancyText' => 'Private vacancy', 'resumeText' => 'Private resume'] === $decoded;
        }))->willReturn('encrypted-input');
        $cipher->expects(self::once())->method('fingerprint')->willReturn(str_repeat('a', 64));
        $metadata = self::createStub(AnalysisProviderMetadata::class);
        $metadata->method('provider')->willReturn('fake');
        $metadata->method('model')->willReturn('fake');

        $request = new QueueAnalysisRequestHandler($users, $requests, $cipher, $metadata)(new QueueAnalysisRequest(
            self::USER_ID,
            AnalysisKind::CandidateFit,
            'Private vacancy',
            'Private resume',
        ));

        self::assertSame(AnalysisKind::CandidateFit, $request->kind);
        self::assertSame('encrypted-input', $request->inputCiphertext);
    }

    public function testRejectsCandidateFitWithoutResumeBeforePersisting(): void
    {
        $users = self::createStub(UserRepository::class);
        $users->method('find')->willReturn(new User());
        $requests = $this->createMock(AnalysisRequestRepository::class);
        $requests->expects(self::never())->method('save');
        $cipher = $this->createMock(AnalysisCipher::class);
        $cipher->expects(self::never())->method('encrypt');

        $this->expectException(\InvalidArgumentException::class);
        new QueueAnalysisRequestHandler($users, $requests, $cipher, self::createStub(AnalysisProviderMetadata::class))(new QueueAnalysisRequest(self::USER_ID, AnalysisKind::CandidateFit, 'Private vacancy'));
    }
}
