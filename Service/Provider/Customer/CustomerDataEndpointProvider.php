<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Customer;

use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\FrontendApi\Service\Provider\Customer\CustomerDataEndpointProviderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;

class CustomerDataEndpointProvider implements CustomerDataEndpointProviderInterface
{
    private const CUSTOMER_DATA_ENDPOINT = 'V1/klevu/customerData';

    /**
     * @var StoreScopeProviderInterface
     */
    private readonly StoreScopeProviderInterface $storeScopeProvider;

    /**
     * @param StoreScopeProviderInterface $storeScopeProvider
     */
    public function __construct(StoreScopeProviderInterface $storeScopeProvider)
    {
        $this->storeScopeProvider = $storeScopeProvider;
    }

    /**
     * @return string
     */
    public function get(): string
    {
        $store = $this->storeScopeProvider->getCurrentStore();
        $storeBaseUrl = '';
        if (method_exists($store, 'getBaseUrl')) {
            /** @var Store $store */
            $storeBaseUrl = $store->getBaseUrl(type: UrlInterface::URL_TYPE_WEB, secure: true);
        }
        $baseUrl = rtrim(
            string: $storeBaseUrl,
            characters: ' /',
        );
        $storeCode = $store
            ? '/' . $store->getCode()
            : '';

        return $baseUrl . '/rest' . $storeCode . '/' . self::CUSTOMER_DATA_ENDPOINT;
    }
}
