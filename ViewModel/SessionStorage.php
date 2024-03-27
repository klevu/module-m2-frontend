<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel;

use Klevu\FrontendApi\Service\Provider\Customer\SessionStoragePropertiesProviderInterface;
use Klevu\FrontendApi\ViewModel\SessionStorageInterface;

class SessionStorage implements SessionStorageInterface
{
    /**
     * @var SessionStoragePropertiesProviderInterface
     */
    private readonly SessionStoragePropertiesProviderInterface $sessionStorageKeyProvider;

    /**
     * @param SessionStoragePropertiesProviderInterface $sessionStorageKeyProvider
     */
    public function __construct(SessionStoragePropertiesProviderInterface $sessionStorageKeyProvider)
    {
        $this->sessionStorageKeyProvider = $sessionStorageKeyProvider;
    }

    /**
     * @return string
     */
    public function getSessionStorageKey(): string
    {
        return $this->sessionStorageKeyProvider->getStorageKey();
    }

    /**
     * @return string
     */
    public function getSessionCustomerDataSectionKey(): string
    {
        return $this->sessionStorageKeyProvider->getCustomerDataSectionKey();
    }
}
