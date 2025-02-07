<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend;

use Klevu\Frontend\Service\Provider\Customer\SessionStoragePropertiesProvider;

class Constants
{
    /**
     * @deprecated 1.0.4 No longer used by internal code and not recommended
     * @see SessionStoragePropertiesProvider::SESSION_STORAGE_KEY
     */
    public const KLEVU_SESSION_STORAGE_KEY = SessionStoragePropertiesProvider::SESSION_STORAGE_KEY;
    /**
     * @deprecated 1.0.4 No longer used by internal code and not recommended
     * @see SessionStoragePropertiesProvider::SESSION_STORAGE_CUSTOMER_DATA_SECTION
     */
    public const KLEVU_SESSION_STORAGE_KEY_CUSTOMER_DATA = SessionStoragePropertiesProvider::SESSION_STORAGE_CUSTOMER_DATA_SECTION; // phpcs:ignore Generic.Files.LineLength.TooLong
    public const JS_EVENTNAME_CUSTOMER_DATA_LOADED = 'klevu.customerData.loaded';
    public const JS_EVENTNAME_CUSTOMER_DATA_LOAD_ERROR = 'klevu.customerData.loadError';
    public const XML_PATH_CUSTOMER_GROUP_PRICING = 'klevu_frontend/pricing/use_customer_groups';
}
