<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Scripts;

final readonly class BenchmarkHttpResponse
{
    /** @param list<string> $headers */
    public function __construct(public int $statusCode, public array $headers, public string $body)
    {
    }
}

/** @param array<string, string> $cookies */
function request(string $method, string $url, array &$cookies, ?string $formData = null): BenchmarkHttpResponse
{
    $headers = ['Connection: close'];
    if ([] !== $cookies) {
        $pairs = [];
        foreach ($cookies as $name => $value) {
            $pairs[] = sprintf('%s=%s', $name, $value);
        }
        $headers[] = 'Cookie: '.implode('; ', $pairs);
    }
    if (null !== $formData) {
        $headers[] = 'Origin: '.originForUrl($url);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $headers[] = 'Content-Length: '.strlen($formData);
    }
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'timeout' => 10,
            'ignore_errors' => true,
            'follow_location' => 0,
            'header' => implode("\r\n", $headers)."\r\n",
            'content' => $formData ?? '',
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $responseHeaders = http_get_last_response_headers() ?? [];
    if (false === $body || [] === $responseHeaders) {
        throw new \RuntimeException(sprintf('Could not complete %s %s.', $method, $url));
    }
    if (1 !== preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $responseHeaders[0], $matches)) {
        throw new \RuntimeException(sprintf('Could not read the HTTP status from %s.', $url));
    }
    foreach ($responseHeaders as $responseHeader) {
        if (1 !== preg_match('/^Set-Cookie:\s*([^=;]+)=([^;]*)/i', $responseHeader, $cookie)) {
            continue;
        }
        $cookies[$cookie[1]] = $cookie[2];
    }

    return new BenchmarkHttpResponse((int) $matches[1], $responseHeaders, $body);
}

function originForUrl(string $url): string
{
    $parts = parse_url($url);
    if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) || !in_array($parts['scheme'], ['http', 'https'], true)) {
        throw new \InvalidArgumentException('The base URL must use HTTP or HTTPS with a host.');
    }

    return sprintf('%s://%s%s', $parts['scheme'], $parts['host'], isset($parts['port']) ? sprintf(':%d', $parts['port']) : '');
}

/** @param list<string> $headers */
function serverTimingDuration(array $headers): float
{
    foreach ($headers as $header) {
        if (1 === preg_match('/^Server-Timing:.*\bapp;dur=(\d+(?:\.\d+)?)/i', $header, $match)) {
            $duration = (float) $match[1];
            if (is_finite($duration)) {
                return $duration;
            }
        }
    }

    throw new \RuntimeException('Missing valid app Server-Timing; enable APP_REQUEST_TIMING=1.');
}

function csrfTokenForTransition(string $html, string $transition): string
{
    $document = new \DOMDocument();
    $errors = libxml_use_internal_errors(true);
    try {
        if (!$document->loadHTML($html)) {
            throw new \RuntimeException('Could not parse the vacancy edit page.');
        }
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($errors);
    }
    $xpath = new \DOMXPath($document);
    $transitions = $xpath->query(sprintf('//form[.//input[@name="transition" and @value="%s"]]', $transition));
    if (false === $transitions || 0 === $transitions->count()) {
        throw new \RuntimeException(sprintf('The vacancy is not ready for the "%s" transition.', $transition));
    }
    $form = $transitions->item(0);
    if (!$form instanceof \DOMNode) {
        throw new \RuntimeException(sprintf('Could not find a form for the "%s" transition.', $transition));
    }
    $tokens = $xpath->query('.//input[@name="_token"]/@value', $form);
    $token = false === $tokens ? null : $tokens->item(0)?->nodeValue;
    if (!is_string($token) || '' === $token) {
        throw new \RuntimeException(sprintf('Could not find a CSRF token for the "%s" transition.', $transition));
    }

    return $token;
}

function csrfTokenForLogin(string $html): string
{
    $document = new \DOMDocument();
    $errors = libxml_use_internal_errors(true);
    try {
        if (!$document->loadHTML($html)) {
            throw new \RuntimeException('Could not parse the login page.');
        }
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($errors);
    }
    $xpath = new \DOMXPath($document);
    $tokens = $xpath->query('//input[@name="_csrf_token"]/@value');
    $token = false === $tokens ? null : $tokens->item(0)?->nodeValue;
    if (!is_string($token) || '' === $token) {
        throw new \RuntimeException('Could not find the login CSRF token.');
    }

    return $token;
}

/** @param list<string> $headers */
function locationHeader(array $headers): ?string
{
    foreach ($headers as $header) {
        if (1 === preg_match('/^Location:\s*(\S+)\s*$/i', $header, $match)) {
            return $match[1];
        }
    }

    return null;
}

function assertSuccessfulLogin(BenchmarkHttpResponse $response): void
{
    $location = locationHeader($response->headers);
    $path = is_string($location) ? parse_url($location, PHP_URL_PATH) : null;
    if ('/' !== $path || !in_array($response->statusCode, [302, 303], true)) {
        throw new \RuntimeException(sprintf('Login failed; expected a redirect to the dashboard, received HTTP %d%s.', $response->statusCode, null === $location ? '' : sprintf(' to %s', $location)));
    }
}

function assertVacancyEditPage(BenchmarkHttpResponse $response, string $vacancyId): void
{
    if (200 === $response->statusCode) {
        return;
    }
    if (404 === $response->statusCode) {
        throw new \RuntimeException(sprintf('Vacancy %s was not found or is not owned by the signed-in account.', $vacancyId));
    }

    throw new \RuntimeException(sprintf('Vacancy edit page returned HTTP %d.', $response->statusCode));
}

/**
 * @param list<float> $samples
 *
 * @return array{median: float, p95: float, max: float}
 */
function timingSummary(array $samples): array
{
    if ([] === $samples) {
        throw new \InvalidArgumentException('At least one timing sample is required.');
    }
    sort($samples, SORT_NUMERIC);
    $count = count($samples);
    $middle = intdiv($count, 2);
    $median = 0 === $count % 2 ? ($samples[$middle - 1] + $samples[$middle]) / 2 : $samples[$middle];

    return [
        'median' => $median,
        'p95' => $samples[(int) ceil($count * 0.95) - 1],
        'max' => $samples[$count - 1],
    ];
}

/**
 * @param list<array{request: int, transition: string, duration: float}> $samples
 *
 * @return list<array{request: int, transition: string, duration: float}>
 */
function thresholdViolations(array $samples, float $limit): array
{
    return array_values(array_filter($samples, static fn (array $sample): bool => $sample['duration'] >= $limit));
}

/** @param array<string, string> $cookies */
function transition(string $baseUrl, string $vacancyId, string $transition, array &$cookies): float
{
    $editUrl = sprintf('%s/vacancies/%s/edit', $baseUrl, rawurlencode($vacancyId));
    $edit = request('GET', $editUrl, $cookies);
    assertVacancyEditPage($edit, $vacancyId);
    $formData = http_build_query([
        '_token' => csrfTokenForTransition($edit->body, $transition),
        'transition' => $transition,
        'return' => sprintf('/vacancies/%s/edit', $vacancyId),
    ]);
    $response = $vacancyId
            |> rawurlencode(...)
            |> (static fn ($x) => sprintf('%s/vacancies/%s/status', $baseUrl, $x))
            |> (static fn ($x) => request('POST', $x, $cookies, $formData));
    if (!in_array($response->statusCode, [302, 303], true)) {
        throw new \RuntimeException(sprintf('Transition %s returned HTTP %d.', $transition, $response->statusCode));
    }

    return serverTimingDuration($response->headers);
}

/**
 * @param list<string> $arguments
 *
 * @return array{baseUrl: string, email: string, vacancyId: string}
 */
function configuration(array $arguments): array
{
    $options = [];
    foreach (array_slice($arguments, 1) as $argument) {
        if (1 !== preg_match('/^--(base-url|email|vacancy)=(.+)$/', $argument, $match)) {
            throw new \InvalidArgumentException(sprintf('Usage: php %s [--base-url=http://127.0.0.1] --email=user@example.test --vacancy=UUID', $arguments[0] ?? 'scripts/benchmark-vacancy-status.php'));
        }
        $options[$match[1]] = $match[2];
    }
    $baseUrl = rtrim($options['base-url'] ?? 'http://127.0.0.1', '/');
    $email = $options['email'] ?? null;
    $vacancyId = $options['vacancy'] ?? null;
    if (!is_string($email) || !is_string($vacancyId)) {
        throw new \InvalidArgumentException(sprintf('Usage: php %s [--base-url=http://127.0.0.1] --email=user@example.test --vacancy=UUID', $arguments[0] ?? 'scripts/benchmark-vacancy-status.php'));
    }
    originForUrl($baseUrl);
    if (1 !== preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $vacancyId)) {
        throw new \InvalidArgumentException('The vacancy must be an RFC 4122 UUID.');
    }

    return ['baseUrl' => $baseUrl, 'email' => $email, 'vacancyId' => $vacancyId];
}

function passwordFromInput(string $input): string
{
    return rtrim($input, "\r\n");
}

function password(): string
{
    if (!function_exists('posix_isatty') || !posix_isatty(STDIN)) {
        throw new \RuntimeException('Run this benchmark from an interactive terminal so the password can be entered safely.');
    }
    $stty = shell_exec('stty -g');
    if (!is_string($stty) || '' === trim($stty)) {
        throw new \RuntimeException('Could not configure hidden password input.');
    }
    fwrite(STDERR, 'Password: ');
    shell_exec('stty -echo');
    try {
        $password = fgets(STDIN);
    } finally {
        $stty
            |> trim(...)
            |> escapeshellarg(...)
            |> (static fn ($x) => sprintf('stty %s', $x))
            |> shell_exec(...);
        fwrite(STDERR, "\n");
    }
    if (false === $password || '' === passwordFromInput($password)) {
        throw new \RuntimeException('A password is required.');
    }

    return passwordFromInput($password);
}

/** @param list<string> $arguments */
function run(array $arguments): int
{
    $configuration = configuration($arguments);
    $cookies = [];
    $login = request('GET', $configuration['baseUrl'].'/login', $cookies);
    if (200 !== $login->statusCode) {
        throw new \RuntimeException(sprintf('Login page returned HTTP %d.', $login->statusCode));
    }
    $loginForm = http_build_query([
        '_username' => $configuration['email'],
        '_password' => password(),
        '_csrf_token' => csrfTokenForLogin($login->body),
    ]);
    $loginResponse = request('POST', $configuration['baseUrl'].'/login', $cookies, $loginForm);
    assertSuccessfulLogin($loginResponse);

    $warmupUrl = sprintf('%s/vacancies/%s/edit', $configuration['baseUrl'], rawurlencode($configuration['vacancyId']));
    for ($request = 0; $request < 3; ++$request) {
        $warmup = request('GET', $warmupUrl, $cookies);
        assertVacancyEditPage($warmup, $configuration['vacancyId']);
    }

    $samples = [];
    for ($request = 1; $request <= 30; ++$request) {
        $transitionName = 1 === $request % 2 ? 'start_applying' : 'undo_start_applying';
        $duration = transition($configuration['baseUrl'], $configuration['vacancyId'], $transitionName, $cookies);
        $samples[] = ['request' => $request, 'transition' => $transitionName, 'duration' => $duration];
        printf("%2d  %-20s %8.3f ms\n", $request, $transitionName, $duration);
    }
    $summary = timingSummary(array_column($samples, 'duration'));
    $violations = thresholdViolations($samples, 15.0);
    printf("\nMedian: %.3f ms; p95: %.3f ms; max: %.3f ms\n", $summary['median'], $summary['p95'], $summary['max']);
    if ([] !== $violations) {
        $violations
            |> count(...)
            |> (static fn ($x) => sprintf("FAIL: %d of 30 requests were at or above 15 ms.\n", $x))
            |> (static fn ($x) => fwrite(STDERR, $x));

        return 1;
    }
    fwrite(STDOUT, "PASS: all 30 transition requests completed below 15 ms.\n");

    return 0;
}

$scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? null;
if (is_string($scriptFilename) && __FILE__ === realpath($scriptFilename)) {
    $arguments = $_SERVER['argv'] ?? null;
    if (!is_array($arguments)) {
        fwrite(STDERR, "Could not read the command-line arguments.\n");
        exit(1);
    }
    $commandArguments = [];
    foreach ($arguments as $argument) {
        if (!is_string($argument)) {
            fwrite(STDERR, "Could not read the command-line arguments.\n");
            exit(1);
        }
        $commandArguments[] = $argument;
    }
    try {
        exit(run($commandArguments));
    } catch (\Throwable $exception) {
        fwrite(STDERR, $exception->getMessage()."\n");
        exit(1);
    }
}
