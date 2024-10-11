<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel;

use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\Frontend\Service\Provider\CurrencyProvider;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\FrontendApi\Service\Provider\CurrencyProviderInterface;
use Klevu\FrontendApi\ViewModel\CurrencyConversionInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CurrencyConversion implements CurrencyConversionInterface
{
    /**
     * @var CurrencyProviderInterface
     */
    private readonly CurrencyProviderInterface $currencyProvider;
    /**
     * @var StoreManagerInterface
     */
    private readonly StoreManagerInterface $storeManager;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var AppState
     */
    private readonly AppState $appState;
    /**
     * @var IsEnabledDeterminerInterface
     */
    private readonly IsEnabledDeterminerInterface $isEnabledDeterminer;
    /**
     * @var mixed[]
     */
    private array $isEnabledConditions = [];

    /**
     * @param CurrencyProviderInterface $currencyProvider
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param AppState $appState
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param mixed[] $isEnabledConditions
     */
    public function __construct(
        CurrencyProviderInterface $currencyProvider,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        AppState $appState,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        array $isEnabledConditions = [],
    ) {
        $this->currencyProvider = $currencyProvider;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->appState = $appState;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->isEnabledConditions = $isEnabledConditions;
    }

    /**
     * @return bool
     * @throws InvalidIsEnabledDeterminerException
     */
    public function isEnabled(): bool
    {
        $return = false;
        try {
            $this->isEnabledDeterminer->executeAnd($this->isEnabledConditions);
            $return = true;
        } catch (InvalidIsEnabledDeterminerException $exception) {
            if ($this->appState->getMode() !== AppState::MODE_PRODUCTION) {
                throw $exception;
            }
            $this->logger->error(
                message: 'Method: {method}, Error: {error}',
                context: [
                    'method' => __METHOD__,
                    'error' => $exception->getMessage(),
                ],
            );
        } catch (OutputDisabledException) { //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            // Output is disabled.
        }

        return $return;
    }

    /**
     * @return float
     */
    public function getExchangeRate(): float
    {
        $data = $this->getCurrencyData();

        return (float)($data[CurrencyProvider::CURRENCY_RATE] ?? 1.0);
    }

    /**
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        $data = $this->getCurrencyData();

        return $data[CurrencyProvider::CURRENCY_SYMBOL] ?? '';
    }

    /**
     * @return int
     */
    public function getCurrencyPrecision(): int
    {
        $data = $this->getCurrencyData();

        return $data[CurrencyProvider::CURRENCY_PRECISION];
    }

    /**
     * @return string
     */
    public function getCurrencyDecimalSymbol(): string
    {
        $data = $this->getCurrencyData();

        return $data[CurrencyProvider::CURRENCY_DECIMAL_SYMBOL];
    }

    /**
     * @return string
     */
    public function getCurrencyGroupSymbol(): string
    {
        $data = $this->getCurrencyData();

        return $data[CurrencyProvider::CURRENCY_GROUP_SYMBOL];
    }

    /**
     * @return int
     */
    public function getCurrencyGroupLength(): int
    {
        $data = $this->getCurrencyData();

        return $data[CurrencyProvider::CURRENCY_GROUP_LENGTH];
    }

    /**
     * @return string
     */
    public function getCurrencyFormat(): string
    {
        $data = $this->getCurrencyData();

        return $data[CurrencyProvider::CURRENCY_FORMAT];
    }

    /**
     * @return string
     */
    public function getCurrencyAppendAtLast(): string
    {
        $data = $this->getCurrencyData();

        return $data[CurrencyProvider::CURRENCY_APPEND_AT_LAST] ? "true" : "false";
    }

    /**
     * @return array<int|float|string>
     */
    private function getCurrencyData(): array
    {
        try {
            /** @var Store $store */
            $store = $this->storeManager->getStore();
        } catch (NoSuchEntityException $exception) {
            $this->logger->error(
                message: 'Method: {method}, Error: {message}',
                context: [
                    'method' => __METHOD__,
                    'message' => $exception->getMessage(),
                ],
            );

            return [];
        }
        $currencies = $this->currencyProvider->get();

        return $currencies[$store->getCurrentCurrencyCode()];
    }
}
