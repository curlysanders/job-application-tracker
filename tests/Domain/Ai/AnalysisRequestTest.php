<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Domain\Ai;

use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisKind;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequest;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisRequested;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisResult;
use CurlySanders\JobApplicationTracker\Domain\Ai\AnalysisState;
use CurlySanders\JobApplicationTracker\Domain\User\User;
use PHPUnit\Framework\TestCase;

final class AnalysisRequestTest extends TestCase
{
    private const string INPUT_FINGERPRINT = 'a8c244a13ba49b7f0e49157ddd40dbffb2d86eb17e107b24b8954ce61a2b7b17';

    public function testRecordsOnlyTheInputFingerprintInItsDurableEvent(): void
    {
        $request = $this->request();

        self::assertCount(1, $request->getRecordedEvents());
        $event = $request->getRecordedEvents()[0];
        self::assertInstanceOf(AnalysisRequested::class, $event);
        self::assertSame(['inputFingerprint' => self::INPUT_FINGERPRINT], $event->changedProperties);
        self::assertStringNotContainsString('Sensitive vacancy text', json_encode($event, JSON_THROW_ON_ERROR));
    }

    public function testReplacingInputDiscardsTheObsoleteResultAndCreatesANewVersion(): void
    {
        $request = $this->request();
        $request->complete(new AnalysisResult($request, 'encrypted-result', str_repeat('b', 64)));

        $request->replaceInput('new-encrypted-input', str_repeat('c', 64), 'fake', 'fake', new \DateTimeImmutable('+24 hours'));

        self::assertSame(2, $request->version);
        self::assertSame(AnalysisState::Pending, $request->state);
        self::assertNull($request->result);
        self::assertTrue($request->isCurrentPending(str_repeat('c', 64)));
        self::assertCount(2, $request->getRecordedEvents());
    }

    public function testAStaleFingerprintCannotChangeTheRequestState(): void
    {
        $request = $this->request();
        $request->fail(str_repeat('d', 64));

        self::assertSame(AnalysisState::Pending, $request->state);
        self::assertNull($request->failureCode);
    }

    public function testMarksTheCurrentRequestAsFailedWithoutRetainingProviderDetails(): void
    {
        $request = $this->request();
        $request->fail(self::INPUT_FINGERPRINT);

        self::assertSame(AnalysisState::Failed, $request->state);
        self::assertSame('provider_failed', $request->failureCode);
    }

    private function request(): AnalysisRequest
    {
        return new AnalysisRequest(
            new User(),
            AnalysisKind::VacancyExtraction,
            'encrypted-input',
            self::INPUT_FINGERPRINT,
            'fake',
            'fake',
            new \DateTimeImmutable('+24 hours'),
        );
    }
}
