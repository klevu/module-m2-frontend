<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel\Html\Head;

use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\FrontendApi\Service\Provider\Customer\CookiePropertiesProviderInterface as CustomerCookieProviderInterface;
use Klevu\FrontendApi\Service\Provider\Customer\CustomerDataEndpointProviderInterface;
use Klevu\FrontendApi\ViewModel\Html\Head\CustomerDataInterface;

class CustomerData implements CustomerDataInterface
{
    private const JS_EVENT_NAME_CUSTOMER_DATA_LOADED = 'klevu.customerData.loaded';
    private const JS_EVENT_NAME_CUSTOMER_DATA_LOAD_ERROR = 'klevu.customerData.loadError';
    private const LOCAL_STORAGE_LIFETIME_CUSTOMER_DATA = 600;

    /**
     * @var CustomerCookieProviderInterface
     */
    private readonly CustomerCookieProviderInterface $customerCookieProvider;
    /**
     * @var CustomerDataEndpointProviderInterface
     */
    private readonly CustomerDataEndpointProviderInterface $customerDataEndpointProvider;
    /**
     * @var IsEnabledDeterminerInterface
     */
    private readonly IsEnabledDeterminerInterface $isEnabledDeterminer;
    /**
     * @var mixed[]
     */
    private readonly array $isEnabledConditions;

    /**
     * @param CustomerCookieProviderInterface $customerCookieProvider
     * @param CustomerDataEndpointProviderInterface $customerDataEndpointProvider
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param mixed[] $isEnabledConditions
     */
    public function __construct(
        CustomerCookieProviderInterface $customerCookieProvider,
        CustomerDataEndpointProviderInterface $customerDataEndpointProvider,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        array $isEnabledConditions = [],
    ) {
        $this->customerCookieProvider = $customerCookieProvider;
        $this->customerDataEndpointProvider = $customerDataEndpointProvider;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->isEnabledConditions = $isEnabledConditions;
    }

    /**
     * @return bool
     * @throws InvalidIsEnabledDeterminerException
     */
    public function isOutputEnabled(): bool
    {
        try {
            $this->isEnabledDeterminer->executeAnd($this->isEnabledConditions);
        } catch (OutputDisabledException) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function getCustomerDataApiEndpoint(): string
    {
        return $this->customerDataEndpointProvider->get();
    }

    /**
     * @return string
     */
    public function getCookieKey(): string
    {
        return $this->customerCookieProvider->getCookieKey();
    }

    /**
     * @return string
     */
    public function getExpireSectionsKey(): string
    {
        return $this->customerCookieProvider->getExpireSectionsKey();
    }

    /**
     * @return string
     */
    public function getCustomerDataKey(): string
    {
        return $this->customerCookieProvider->getCustomerDataSectionKey();
    }

    /**
     * @return int
     */
    public function getCustomerDataSectionLifetime(): int
    {
        return self::LOCAL_STORAGE_LIFETIME_CUSTOMER_DATA;
    }

    /**
     * @return string
     */
    public function getCustomerDataLoadedEventName(): string
    {
        return self::JS_EVENT_NAME_CUSTOMER_DATA_LOADED;
    }

    /**
     * @return string
     */
    public function getCustomerDataLoadErrorEventName(): string
    {
        return self::JS_EVENT_NAME_CUSTOMER_DATA_LOAD_ERROR;
    }
}
