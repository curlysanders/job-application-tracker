<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Scripts;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2).'/scripts/benchmark-vacancy-status.php';

use CurlySanders\JobApplicationTracker\Scripts\BenchmarkHttpResponse;

use function CurlySanders\JobApplicationTracker\Scripts\assertSuccessfulLogin;
use function CurlySanders\JobApplicationTracker\Scripts\assertVacancyEditPage;
use function CurlySanders\JobApplicationTracker\Scripts\configuration;
use function CurlySanders\JobApplicationTracker\Scripts\csrfTokenForLogin;
use function CurlySanders\JobApplicationTracker\Scripts\csrfTokenForTransition;
use function CurlySanders\JobApplicationTracker\Scripts\originForUrl;
use function CurlySanders\JobApplicationTracker\Scripts\passwordFromInput;
use function CurlySanders\JobApplicationTracker\Scripts\serverTimingDuration;
use function CurlySanders\JobApplicationTracker\Scripts\thresholdViolations;
use function CurlySanders\JobApplicationTracker\Scripts\timingSummary;

final class BenchmarkVacancyStatusTest extends TestCase
{
    public function testExtractsTheServerTimingApplicationDuration(): void
    {
        self::assertSame(3.125, serverTimingDuration(['Content-Type: text/html', 'Server-Timing: cache;desc="miss", app;dur=3.125']));
    }

    public function testRejectsMissingServerTimingApplicationDuration(): void
    {
        $this->expectException(\RuntimeException::class);
        serverTimingDuration(['Server-Timing: cache;desc="miss"']);
    }

    public function testExtractsTheCsrfTokenFromTheRequestedTransitionForm(): void
    {
        $html = '<form><input name="_token" value="bookmarked-token"><input name="transition" value="start_applying"></form><form><input name="_token" value="applying-token"><input name="transition" value="undo_start_applying"></form>';

        self::assertSame('applying-token', csrfTokenForTransition($html, 'undo_start_applying'));
    }

    public function testExtractsTheLoginCsrfToken(): void
    {
        self::assertSame('login-token', csrfTokenForLogin('<form><input name="_csrf_token" value="login-token"></form>'));
    }

    public function testAcceptsOnlyTheDashboardRedirectAfterLogin(): void
    {
        assertSuccessfulLogin(new BenchmarkHttpResponse(302, ['Location: /'], ''));

        $this->assertExceptionMessage(
            'Login failed; expected a redirect to the dashboard, received HTTP 302 to /login.',
            static fn (): null => assertSuccessfulLogin(new BenchmarkHttpResponse(302, ['Location: /login'], '')),
        );
    }

    public function testExplainsAnUnavailableVacancy(): void
    {
        $this->assertExceptionMessage(
            'Vacancy 01a0fc15-4e4d-7cb0-830e-60a35bc62838 was not found or is not owned by the signed-in account.',
            static fn (): null => assertVacancyEditPage(new BenchmarkHttpResponse(404, [], ''), '01a0fc15-4e4d-7cb0-830e-60a35bc62838'),
        );
    }

    public function testRejectsMalformedVacancyIdentifiers(): void
    {
        $this->assertExceptionMessage(
            'The vacancy must be an RFC 4122 UUID.',
            static fn (): array => configuration(['benchmark.php', '--email=user@example.test', '--vacancy=invalid']),
        );
    }

    public function testDerivesTheSameOriginHeaderFromTheBaseUrl(): void
    {
        self::assertSame('http://127.0.0.1:18081', originForUrl('http://127.0.0.1:18081/vacancies/1/edit'));
        self::assertSame('https://tracker.example.test', originForUrl('https://tracker.example.test'));
    }

    public function testRejectsBaseUrlsWithoutAnHttpHost(): void
    {
        $this->assertExceptionMessage(
            'The base URL must use HTTP or HTTPS with a host.',
            static fn (): string => originForUrl('http:///vacancies'),
        );
    }

    public function testRemovesOnlyThePasswordInputLineEnding(): void
    {
        self::assertSame(' password ', passwordFromInput(" password \r\n"));
    }

    public function testSummarizesAndEnforcesTheStrictTimingThreshold(): void
    {
        self::assertSame(['median' => 2.5, 'p95' => 4.0, 'max' => 4.0], timingSummary([1.0, 2.0, 3.0, 4.0]));
        self::assertSame([['request' => 2, 'transition' => 'undo_start_applying', 'duration' => 15.0]], thresholdViolations([
            ['request' => 1, 'transition' => 'start_applying', 'duration' => 14.999],
            ['request' => 2, 'transition' => 'undo_start_applying', 'duration' => 15.0],
        ], 15.0));
    }

    /** @param \Closure(): mixed $callback */
    private function assertExceptionMessage(string $expectedMessage, \Closure $callback): void
    {
        try {
            $callback();
            self::fail('Expected an exception.');
        } catch (\Throwable $exception) {
            self::assertSame($expectedMessage, $exception->getMessage());
        }
    }
}
