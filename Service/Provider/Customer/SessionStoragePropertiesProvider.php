<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Customer;

use Klevu\FrontendApi\Service\Provider\Customer\SessionStoragePropertiesProviderInterface;

class SessionStoragePropertiesProvider implements SessionStoragePropertiesProviderInterface
{
    private const SESSION_STORAGE_KEY = 'klv_mage';
    private const SESSION_STORAGE_CUSTOMER_DATA_SECTION = 'customerData';

    /**
     * @return string
     */
    public function getStorageKey(): string
    {
        return self::SESSION_STORAGE_KEY;
    }

    /**
     * @return string
     */
    public function getCustomerDataSectionKey(): string
    {
        return self::SESSION_STORAGE_CUSTOMER_DATA_SECTION;
    }
}
