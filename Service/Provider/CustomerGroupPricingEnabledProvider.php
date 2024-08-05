<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\FrontendApi\Service\Provider\CustomerGroupPricingEnabledProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CustomerGroupPricingEnabledProvider implements CustomerGroupPricingEnabledProviderInterface
{
    public const XML_PATH_USE_CUSTOMER_GROUP_PRICING = 'klevu_frontend/pricing/use_customer_groups';

    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeProviderInterface $scopeProvider
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ScopeProviderInterface $scopeProvider,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->scopeProvider = $scopeProvider;
    }

    /**
     * @return bool
     */
    public function get(): bool
    {
        $scope = $this->scopeProvider->getCurrentScope();
        $scopeObject = $scope->getScopeObject();

        return $this->scopeConfig->isSetFlag(
            static::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            $scope->getScopeType(),
            $scopeObject?->getCode(),
        );
    }
}
