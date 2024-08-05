<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\DelayPowerUpCondition;

use Klevu\FrontendApi\Service\DelayPowerUpCondition\DelayPowerUpConditionInterface;
use Klevu\FrontendApi\Service\Provider\CustomerGroupPricingEnabledProviderInterface;

class IsCustomerGroupPricingEnabledCondition implements DelayPowerUpConditionInterface
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
