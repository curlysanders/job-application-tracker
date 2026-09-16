<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Http;

use CurlySanders\JobApplicationTracker\Infrastructure\Http\RequestTimingSubscriber;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RequestTimingSubscriberTest extends TestCase
{
    public function testConsecutiveRequestsHaveIndependentTimings(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('info')->with(
            'Application request completed',
            self::callback(static fn (array $context): bool => 'app_home' === $context['route'] && 200 === $context['status'] && $context['duration_ms'] >= 0),
        );
        $subscriber = new RequestTimingSubscriber(true, $logger);
        $kernel = self::createStub(HttpKernelInterface::class);

        for ($i = 0; $i < 2; ++$i) {
            $request = Request::create('/');
            $request->attributes->set('_route', 'app_home');
            $response = new Response();
            $response->headers->set('Server-Timing', 'other;dur=1');
            $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
            $subscriber->onResponse($event);
            $initialHeaders = $response->headers->all('Server-Timing');
            self::assertSame(['other;dur=1'], $initialHeaders);
            $subscriber->onRequest(new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST));
            $subscriber->onResponse($event);
            $timingHeaders = $response->headers->all('Server-Timing');
            self::assertCount(2, $timingHeaders);
            self::assertSame('other;dur=1', $timingHeaders[0]);
            self::assertIsString($timingHeaders[1]);
            self::assertMatchesRegularExpression('/^app;dur=\d+\.\d{3}$/', $timingHeaders[1]);
            $subscriber->onResponse($event);
            self::assertCount(2, $response->headers->all('Server-Timing'));
        }
    }

    public function testDisabledTimingAndSubrequestsAreIgnored(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('info');
        $kernel = self::createStub(HttpKernelInterface::class);

        foreach ([[false, HttpKernelInterface::MAIN_REQUEST], [true, HttpKernelInterface::SUB_REQUEST]] as [$enabled, $type]) {
            $subscriber = new RequestTimingSubscriber($enabled, $logger);
            $request = Request::create('/');
            $response = new Response();
            $subscriber->onRequest(new RequestEvent($kernel, $request, $type));
            $subscriber->onResponse(new ResponseEvent($kernel, $request, $type, $response));
            self::assertFalse($response->headers->has('Server-Timing'));
        }
    }
}
