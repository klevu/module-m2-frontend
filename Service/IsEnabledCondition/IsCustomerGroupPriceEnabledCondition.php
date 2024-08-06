<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\IsEnabledCondition;

use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;
use Klevu\FrontendApi\Service\Provider\CustomerGroupPricingEnabledProviderInterface;

class IsCustomerGroupPriceEnabledCondition implements IsEnabledConditionInterface
{
    /**
     * @var CustomerGroupPricingEnabledProviderInterface
     */
    private readonly CustomerGroupPricingEnabledProviderInterface $customerGroupPricingEnabledProvider;

    /**
     * @param CustomerGroupPricingEnabledProviderInterface $customerGroupPricingEnabledProvider
     */
    public function __construct(CustomerGroupPricingEnabledProviderInterface $customerGroupPricingEnabledProvider)
    {
        $this->customerGroupPricingEnabledProvider = $customerGroupPricingEnabledProvider;
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        return $this->customerGroupPricingEnabledProvider->get();
    }
}
