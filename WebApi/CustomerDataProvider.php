<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\WebApi;

use Klevu\FrontendApi\Api\CustomerDataProviderInterface;
use Klevu\FrontendApi\Api\Data\CustomerDataInterface;
use Klevu\FrontendApi\Service\Provider\Customer\CustomerDataProviderInterface as CustomerDataProviderServiceInterface;

class CustomerDataProvider implements CustomerDataProviderInterface
{
    /**
     * @var CustomerDataProviderServiceInterface
     */
    private readonly CustomerDataProviderServiceInterface $customerDataProvider;

    /**
     * @param CustomerDataProviderServiceInterface $customerDataProvider
     */
    public function __construct(CustomerDataProviderServiceInterface $customerDataProvider)
    {
        $this->customerDataProvider = $customerDataProvider;
    }

    /**
     * @return CustomerDataInterface
     */
    public function get(): CustomerDataInterface
    {
        return $this->customerDataProvider->get();
    }
}
