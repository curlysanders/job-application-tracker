<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

final class LayoutTest extends WebTestCase
{
    public function testHomeRendersSharedLayoutAndLocalAssets(): void
    {
        $client = self::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Your job search');
        self::assertSelectorExists('meta[name="viewport"]');
        self::assertSelectorExists('nav[aria-label="Main navigation"] a[aria-current="page"]');
        self::assertSelectorExists('button[data-collapse-toggle="app-sidebar"]');
        self::assertSelectorExists('script[type="importmap"]');
        self::assertSelectorExists('link[href^="/assets/"]');
        self::assertResponseNotHasHeader('Server-Timing');
    }

    public function testPreviewRendersInteractiveComponents(): void
    {
        $client = self::createClient();
        $client->request('GET', '/_components');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(4, '.flash');
        self::assertSelectorExists('[data-dropdown-toggle="preview-dropdown"]');
        self::assertSelectorExists('[data-modal-toggle="preview-modal"]');
        self::assertSelectorExists('[data-modal-hide="preview-modal"]');

        $client->request('GET', '/');
        self::assertSelectorNotExists('.flash');
    }

    public function testFlashEscapingFallbackAndConsumption(): void
    {
        self::bootKernel();
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('custom', '<script>alert("unsafe")</script>');
        $request = Request::create('/');
        $request->setSession($session);
        $stack = self::getContainer()->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $stack);
        $stack->push($request);
        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        try {
            $html = $twig->render('layout/flashes.html.twig');
            self::assertStringContainsString('flash-info', $html);
            self::assertStringContainsString('&lt;script&gt;', $html);
            self::assertStringNotContainsString('<script>', $html);
            self::assertSame('', trim($twig->render('layout/flashes.html.twig')));
        } finally {
            $stack->pop();
        }
    }

    public function testPreviewRouteIsAbsentInProduction(): void
    {
        $kernel = self::bootKernel(['environment' => 'prod', 'debug' => false]);
        $router = $kernel->getContainer()->get('router');
        self::assertInstanceOf(RouterInterface::class, $router);
        self::assertNull($router->getRouteCollection()->get('app_component_preview'));
    }
}
