<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Cookie;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\FrontendApi\Service\Provider\Cookie\CookieDurationProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\Config as SessionConfig;

class CookieDurationProvider implements CookieDurationProviderInterface
{
    private const DEFAULT_COOKIE_LIFETIME = 86400;

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
     * @return int
     */
    public function get(): int
    {
        $scope = $this->scopeProvider->getCurrentScope();
        $duration = $this->scopeConfig->getValue(
            SessionConfig::XML_PATH_COOKIE_LIFETIME,
            $scope->getScopeType(),
            $scope->getScopeId(),
        );

        return null !== $duration
            ? (int)$duration
            : self::DEFAULT_COOKIE_LIFETIME;
    }
}
