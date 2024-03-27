<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Customer;

use Klevu\FrontendApi\Service\Provider\Customer\CookiePropertiesProviderInterface;

class CookiePropertiesProvider implements CookiePropertiesProviderInterface
{
    private const COOKIE_KEY = 'klv_mage';
    private const COOKIE_EXPIRE_SECTIONS_KEY = 'expire_sections';
    private const COOKIE_EXPIRE_SECTION_CUSTOMER_DATA_KEY = 'customerData';

    /**
     * @return string
     */
    public function getCookieKey(): string
    {
        return self::COOKIE_KEY;
    }

    /**
     * @return string
     */
    public function getExpireSectionsKey(): string
    {
        return self::COOKIE_EXPIRE_SECTIONS_KEY;
    }

    /**
     * @return string
     */
    public function getCustomerDataSectionKey(): string
    {
        return self::COOKIE_EXPIRE_SECTION_CUSTOMER_DATA_KEY;
    }
}
