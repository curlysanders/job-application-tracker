<?php

declare(strict_types=1);

namespace CurlySanders\JobApplicationTracker\Infrastructure\Currency;

use CurlySanders\JobApplicationTracker\Application\Currency\ExchangeRateProvider;
use CurlySanders\JobApplicationTracker\Application\Currency\ExchangeRates;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsAlias(ExchangeRateProvider::class)]
final readonly class EcbExchangeRateProvider implements ExchangeRateProvider
{
    /** @var list<string> */
    private const array SUPPORTED_CURRENCIES = ['EUR', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK', 'GBP', 'HKD', 'HUF', 'IDR', 'INR', 'ISK', 'JPY', 'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'SEK', 'SGD', 'THB', 'TRY', 'USD', 'ZAR'];

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(service: 'cache.app')] private CacheItemPoolInterface $cache,
    ) {
    }

    public function supportedCurrencyCodes(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    public function latestRates(): ?ExchangeRates
    {
        $item = $this->cache->getItem('exchange_rates.ecb.daily');
        $cached = $item->isHit() ? $this->ratesFrom($item->get()) : null;
        if (null !== $cached && $cached->fetchedAt > new \DateTimeImmutable('-24 hours')) {
            return $cached;
        }

        try {
            $rates = $this->fetchRates();
            $item->set(['fetchedAt' => $rates->fetchedAt->format(DATE_ATOM), 'ratesPerEuro' => $rates->ratesPerEuro]);
            $item->expiresAfter(7 * 24 * 60 * 60);
            $this->cache->save($item);

            return $rates;
        } catch (\Throwable) {
            return null !== $cached && $cached->fetchedAt > new \DateTimeImmutable('-7 days') ? $cached : null;
        }
    }

    private function fetchRates(): ExchangeRates
    {
        $response = $this->httpClient->request('GET', 'https://data-api.ecb.europa.eu/service/data/EXR/D..EUR.SP00.A', ['query' => ['lastNObservations' => 1, 'format' => 'csvdata'], 'timeout' => 2.0]);
        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException('The ECB exchange-rate service returned an unexpected status.');
        }
        $lines = preg_split('/\R/', trim($response->getContent(false)));
        if (false === $lines) {
            $lines = [];
        }
        $header = str_getcsv(array_shift($lines) ?? '', ',', '"', '\\');
        $currencyIndex = array_search('CURRENCY', $header, true);
        $valueIndex = array_search('OBS_VALUE', $header, true);
        if (false === $currencyIndex || false === $valueIndex) {
            throw new \RuntimeException('The ECB exchange-rate response has an unexpected format.');
        }
        $rates = ['EUR' => '1'];
        foreach ($lines as $line) {
            $row = str_getcsv($line, ',', '"', '\\');
            $currency = $row[$currencyIndex] ?? null;
            $value = $row[$valueIndex] ?? null;
            if (is_string($currency) && is_string($value) && is_numeric($value) && in_array($currency, self::SUPPORTED_CURRENCIES, true)) {
                $rates[$currency] = $value;
            }
        }

        return new ExchangeRates($rates, new \DateTimeImmutable());
    }

    private function ratesFrom(mixed $cached): ?ExchangeRates
    {
        if (!is_array($cached) || !is_string($cached['fetchedAt'] ?? null) || !is_array($cached['ratesPerEuro'] ?? null)) {
            return null;
        }
        /** @var array<string, string> $rates */
        $rates = $cached['ratesPerEuro'];

        return new ExchangeRates($rates, new \DateTimeImmutable($cached['fetchedAt']));
    }
}
