<?php

declare(strict_types=1);

// Run against a warmed, production-mode worker with APP_REQUEST_TIMING=1.
$url = $argv[1] ?? 'http://127.0.0.1:18080/';
if (!in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
    fwrite(STDERR, "Expected an HTTP(S) URL.\n");
    exit(1);
}

$samples = [];
for ($i = 0; $i < 120; ++$i) {
    $context = stream_context_create(['http' => [
        'timeout' => 10,
        'ignore_errors' => true,
        'follow_location' => 0,
        'header' => "Connection: close\r\n",
    ]]);
    $body = @file_get_contents($url, false, $context);
    $headers = http_get_last_response_headers() ?? [];
    if (false === $body || !preg_match('/^HTTP\/\S+ 200\b/', $headers[0] ?? '')) {
        fwrite(STDERR, sprintf("Request %d failed: %s\n", $i + 1, $headers[0] ?? 'no response'));
        exit(1);
    }

    $duration = null;
    foreach ($headers as $header) {
        if (preg_match('/^Server-Timing:.*\bapp;dur=(\d+(?:\.\d+)?)/i', $header, $match)) {
            $duration = (float) $match[1];
        }
    }
    if (null === $duration || !is_finite($duration)) {
        fwrite(STDERR, "Missing valid app Server-Timing; enable APP_REQUEST_TIMING=1.\n");
        exit(1);
    }
    if ($i >= 20) {
        $samples[] = $duration;
    }
}
sort($samples, SORT_NUMERIC);
$median = ($samples[49] + $samples[50]) / 2;
$p95 = $samples[94];
printf("URL: %s\nWarmup: 20; samples: 100; concurrency: 1\nMedian: %.3f ms; p95: %.3f ms; max: %.3f ms\n%s (p95 < 30 ms)\n", $url, $median, $p95, $samples[99], $p95 < 30 ? 'PASS' : 'FAIL');
exit($p95 < 30 ? 0 : 1);
