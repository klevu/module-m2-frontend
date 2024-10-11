<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\FrontendApi\Service\Provider\Currency\RatesProviderInterface;
use Klevu\FrontendApi\Service\Provider\CurrencyProviderInterface;
use Magento\Framework\Currency\Exception\CurrencyException;
use Magento\Framework\Locale\CurrencyInterface as LocaleCurrencyInterface;
use Magento\Framework\Locale\FormatInterface as LocaleFormatInterface;

class CurrencyProvider implements CurrencyProviderInterface
{
    public const CURRENCY_RATE = 'rate';
    public const CURRENCY_SYMBOL = 'symbol';
    public const CURRENCY_PRECISION = 'precision';
    public const CURRENCY_DECIMAL_SYMBOL = 'decimalSymbol';
    public const CURRENCY_GROUP_SYMBOL = 'groupSymbol';
    public const CURRENCY_GROUP_LENGTH = 'groupLength';
    public const CURRENCY_APPEND_AT_LAST = 'appendCurrencyAtLast';
    public const CURRENCY_FORMAT = 'format';
    public const DEFAULT_CURRENCY_PRECISION = 2;
    public const DEFAULT_CURRENCY_DECIMAL_SYMBOL = '.';
    public const DEFAULT_CURRENCY_GROUPING_SYMBOL = ',';
    public const DEFAULT_CURRENCY_GROUPING_LENGTH = 3;

    /**
     * @var RatesProviderInterface
     */
    private readonly RatesProviderInterface $ratesProvider;
    /**
     * @var LocaleCurrencyInterface
     */
    private readonly LocaleCurrencyInterface $localeCurrency;
    /**
     * @var LocaleFormatInterface
     */
    private readonly LocaleFormatInterface $localeFormat;
    /**
     * @var array<string, array<string, float|string>>|null
     */
    private ?array $currencyRates = null;
    /**
     * @var mixed[]
     */
    private array $currencyFormat = [];

    /**
     * @param RatesProviderInterface $ratesProvider
     * @param LocaleCurrencyInterface $localeCurrency
     * @param LocaleFormatInterface $localeFormat
     */
    public function __construct(
        RatesProviderInterface $ratesProvider,
        LocaleCurrencyInterface $localeCurrency,
        LocaleFormatInterface $localeFormat,
    ) {
        $this->ratesProvider = $ratesProvider;
        $this->localeCurrency = $localeCurrency;
        $this->localeFormat = $localeFormat;
    }

    /**
     * @return array<string, array<string, int|float|string>>
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
                $this->currencyRates[$currencyCode][static::CURRENCY_PRECISION] = $this->getCurrencyPrecision(
                    currencyCode: $currencyCode,
                );
                $this->currencyRates[$currencyCode][static::CURRENCY_DECIMAL_SYMBOL] = $this->getCurrencyDecimalSeparator( // phpcs:ignore Generic.Files.LineLength.TooLong
                    currencyCode: $currencyCode,
                );
                $this->currencyRates[$currencyCode][static::CURRENCY_GROUP_SYMBOL] = $this->getCurrencyGroupingSeparator( // phpcs:ignore Generic.Files.LineLength.TooLong
                    currencyCode: $currencyCode,
                );
                $this->currencyRates[$currencyCode][static::CURRENCY_GROUP_LENGTH] = $this->getCurrencyGroupingLength(
                    currencyCode: $currencyCode,
                );
                $this->currencyRates[$currencyCode][static::CURRENCY_APPEND_AT_LAST] = $this->getAppendAtLast(
                    currencyCode: $currencyCode,
                );
                $this->currencyRates[$currencyCode][static::CURRENCY_FORMAT] = $this->getFormat(
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
     * @throws CurrencyException
     */
    private function getCurrencySymbol(string $currencyCode): string
    {
        $format = $this->getCurrencyFormat(currency: $currencyCode);
        if (($format['pattern'] ?? null)) {
            $pattern = str_replace(
                    search: [' ', '\u00A0', ' ', '%s', ' ', '\u00A0', ' '],
                    replace: '',
                    subject: $format['pattern'],
                );

            return trim($pattern);
        }
        $localeCurrency = $this->localeCurrency->getCurrency(currency: $currencyCode);

        return $localeCurrency->getSymbol()
            ?: $localeCurrency->getShortName();
    }

    /**
     * @param string $currencyCode
     *
     * @return int
     */
    private function getCurrencyPrecision(string $currencyCode): int
    {
        $format = $this->getCurrencyFormat(currency: $currencyCode);

        return (int)($format['precision'] ?? static::DEFAULT_CURRENCY_PRECISION);
    }

    /**
     * @param string $currencyCode
     *
     * @return string
     */
    private function getCurrencyDecimalSeparator(string $currencyCode): string
    {
        $format = $this->getCurrencyFormat(currency: $currencyCode);

        return $format['decimalSymbol'] ?? static::DEFAULT_CURRENCY_DECIMAL_SYMBOL;
    }

    /**
     * @param string $currencyCode
     *
     * @return string
     */
    private function getCurrencyGroupingSeparator(string $currencyCode): string
    {
        $format = $this->getCurrencyFormat(currency: $currencyCode);

        return $format['groupSymbol'] ?? static::DEFAULT_CURRENCY_GROUPING_SYMBOL;
    }

    /**
     * @param string $currencyCode
     *
     * @return int
     */
    private function getCurrencyGroupingLength(string $currencyCode): int
    {
        $format = $this->getCurrencyFormat(currency: $currencyCode);

        return $format['groupLength'] ?? static::DEFAULT_CURRENCY_GROUPING_LENGTH;
    }

    /**
     * @param string $currencyCode
     *
     * @return bool
     */
    private function getAppendAtLast(string $currencyCode): bool
    {
        $format = $this->getCurrencyFormat(currency: $currencyCode);

        return isset($format['pattern']) && str_starts_with($format['pattern'], '%s');
    }

    /**
     * @param string $currencyCode
     *
     * @return string
     * @throws CurrencyException
     */
    private function getFormat(string $currencyCode): string
    {
        $format = $this->getCurrencyFormat(currency: $currencyCode);
        $currencySymbol = $this->getCurrencySymbol(currencyCode: $currencyCode);

        return str_replace(
            $currencySymbol,
            '%s',
            $format['pattern'],
        );
    }

    /**
     * @param string $currency
     *
     * @return mixed[]
     */
    private function getCurrencyFormat(string $currency): array
    {
        if (!($this->currencyFormat[$currency] ?? null)) {
            // @phpstan-ignore-next-line interface missing arguments \Magento\Framework\Locale\Format::getPriceFormat
            $this->currencyFormat[$currency] = $this->localeFormat->getPriceFormat(null, $currency);
        }

        return $this->currencyFormat[$currency];
    }
}
