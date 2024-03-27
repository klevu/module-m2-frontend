<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Cookie;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\FrontendApi\Service\Provider\Cookie\CookiePathProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Session\Config as SessionConfig;

class CookiePathProvider implements CookiePathProviderInterface
{
    private const DEFAULT_COOKIE_PATH = '/';

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
     * @return string
     */
    public function get(): string
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $this->scopeConfig->getValue(
            SessionConfig::XML_PATH_COOKIE_PATH,
            $scope->getScopeType(),
            $scope->getScopeId(),
        ) ?? self::DEFAULT_COOKIE_PATH;
    }
}
