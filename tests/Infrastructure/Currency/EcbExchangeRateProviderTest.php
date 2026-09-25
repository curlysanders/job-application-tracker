<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Tests\Infrastructure\Currency;

use CurlySanders\JobApplicationTracker\Infrastructure\Currency\EcbExchangeRateProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class EcbExchangeRateProviderTest extends TestCase
{
    public function testItReturnsTheCurrentEcbRatesForSupportedCurrencies(): void
    {
        $provider = new EcbExchangeRateProvider(
            new MockHttpClient([new MockResponse("CURRENCY,OBS_VALUE\nUSD,1.1734\nGBP,0.86880\nDEM,1.95583\n")]),
            new ArrayAdapter(),
        );

        $rates = $provider->latestRates();

        self::assertNotNull($rates);
        self::assertSame(['EUR' => '1', 'USD' => '1.1734', 'GBP' => '0.86880'], $rates->ratesPerEuro);
        self::assertContains('EUR', $provider->supportedCurrencyCodes());
        self::assertNotContains('DEM', $provider->supportedCurrencyCodes());
    }

    public function testItUsesRecentCachedRatesWhenTheEcbRequestFails(): void
    {
        $cache = new ArrayAdapter();
        $item = $cache->getItem('exchange_rates.ecb.daily');
        $item->set([
            'fetchedAt' => new \DateTimeImmutable('-2 days')->format(DATE_ATOM),
            'ratesPerEuro' => ['EUR' => '1', 'USD' => '1.2'],
        ]);
        $cache->save($item);
        $provider = new EcbExchangeRateProvider(
            new MockHttpClient([new MockResponse('', ['http_code' => 503])]),
            $cache,
        );

        $rates = $provider->latestRates();

        self::assertNotNull($rates);
        self::assertSame(['EUR' => '1', 'USD' => '1.2'], $rates->ratesPerEuro);
    }
}
