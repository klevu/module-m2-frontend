<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend;

class Constants
{
    public const KLEVU_SESSION_STORAGE_KEY = 'klv_mage';
    public const KLEVU_SESSION_STORAGE_KEY_CUSTOMER_DATA = 'customerData';
    public const JS_EVENTNAME_CUSTOMER_DATA_LOADED = 'klevu.customerData.loaded';
    public const JS_EVENTNAME_CUSTOMER_DATA_LOAD_ERROR = 'klevu.customerData.loadError';
}
