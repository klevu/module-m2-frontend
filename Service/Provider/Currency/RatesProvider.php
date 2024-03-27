<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Currency;

use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\FrontendApi\Service\Provider\Currency\RatesProviderInterface;
use Magento\Directory\Model\ResourceModel\Currency as CurrencyResourceModel;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;

class RatesProvider implements RatesProviderInterface
{
    /**
     * @var CurrencyResourceModel
     */
    private readonly CurrencyResourceModel $currencyResourceModel;
    /**
     * @var StoreScopeProviderInterface
     */
    private readonly StoreScopeProviderInterface $storeScopeProvider;

    /**
     * @param CurrencyResourceModel $currencyResourceModel
     * @param StoreScopeProviderInterface $storeScopeProvider
     */
    public function __construct(
        CurrencyResourceModel $currencyResourceModel,
        StoreScopeProviderInterface $storeScopeProvider,
    ) {
        $this->currencyResourceModel = $currencyResourceModel;
        $this->storeScopeProvider = $storeScopeProvider;
    }

    /**
     * @return string[]
     */
    public function get(): array
    {
        $store = $this->storeScopeProvider->getCurrentStore();
        if (!$store) {
            return [];
        }

        return $this->currencyResourceModel->getCurrencyRates(
            currency: $this->getBaseCurrencyCode($store),
            toCurrencies: $this->getAvailableCurrencyCodes($store),
        );
    }

    /**
     * @param StoreInterface $store
     *
     * @return string|null
     */
    private function getBaseCurrencyCode(StoreInterface $store): ?string
    {
        $return = null;
        if (method_exists($store, 'getBaseCurrencyCode')) {
            $return = $store->getBaseCurrencyCode();
        }

        return $return;
    }

    /**
     * @param StoreInterface $store
     *
     * @return string[]
     */
    private function getAvailableCurrencyCodes(StoreInterface $store): array
    {
        /** @var Store $store */
        $codes = $store->getAvailableCurrencyCodes();

        return array_values($codes);
    }
}
