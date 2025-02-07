<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Customer;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\FrontendApi\Service\Provider\Customer\SessionStoragePropertiesProviderInterface;
use Magento\Store\Api\Data\StoreInterface;

class SessionStoragePropertiesProvider implements SessionStoragePropertiesProviderInterface
{
    public const SESSION_STORAGE_KEY = 'klv_mage';
    public const SESSION_STORAGE_CUSTOMER_DATA_SECTION = 'customerData';

    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;

    /**
     * @param ScopeProviderInterface $scopeProvider
     */
    public function __construct(ScopeProviderInterface $scopeProvider)
    {
        $this->scopeProvider = $scopeProvider;
    }

    /**
     * @return string
     */
    public function getStorageKey(): string
    {
        return static::SESSION_STORAGE_KEY . $this->getPrefixedStoreCode();
    }

    /**
     * @return string
     */
    public function getCustomerDataSectionKey(): string
    {
        return static::SESSION_STORAGE_CUSTOMER_DATA_SECTION;
    }

    /**
     * @return string
     */
    private function getPrefixedStoreCode(): string
    {
        $currentScope = $this->scopeProvider->getCurrentScope();
        $scope = $currentScope->getScopeObject();

        return ($scope instanceof StoreInterface)
            ? '_' . $scope->getCode()
            : '';
    }
}
