<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\FrontendApi\Service\Provider\Currency\RatesProviderInterface;
use Klevu\FrontendApi\Service\Provider\CurrencyProviderInterface;
use Magento\Framework\Locale\CurrencyInterface as LocaleCurrencyInterface;

class CurrencyProvider implements CurrencyProviderInterface
{
    public const CURRENCY_RATE = 'rate';
    public const CURRENCY_SYMBOL = 'symbol';

    /**
     * @var RatesProviderInterface
     */
    private readonly RatesProviderInterface $ratesProvider;
    /**
     * @var LocaleCurrencyInterface
     */
    private readonly LocaleCurrencyInterface $localeCurrency;
    /**
     * @var array<string, array<string, float|string>>|null
     */
    private ?array $currencyRates = null;

    /**
     * @param RatesProviderInterface $ratesProvider
     * @param LocaleCurrencyInterface $localeCurrency
     */
    public function __construct(
        RatesProviderInterface $ratesProvider,
        LocaleCurrencyInterface $localeCurrency,
    ) {
        $this->ratesProvider = $ratesProvider;
        $this->localeCurrency = $localeCurrency;
    }

    /**
     * @return array<string, array<string, float|string>>
     */
    public function get(): array
    {
        if (null === $this->currencyRates) {
            $exchangeRates = $this->ratesProvider->get();
            if (!$exchangeRates) {
                return [];
            }
            $this->currencyRates = [];
            foreach ($exchangeRates as $currencyCode => $exchangeRate) {
                $this->currencyRates[$currencyCode][static::CURRENCY_RATE] = (float)$exchangeRate;
                $this->currencyRates[$currencyCode][static::CURRENCY_SYMBOL] = $this->getCurrencySymbol(
                    currencyCode: $currencyCode,
                );
            }
        }

        return $this->currencyRates;
    }

    /**
     * @param string $currencyCode
     *
     * @return string
     */
    private function getCurrencySymbol(string $currencyCode): string
    {
        $localeCurrency = $this->localeCurrency->getCurrency(currency: $currencyCode);

        return $localeCurrency->getSymbol()
            ?: $localeCurrency->getShortName();
    }
}
