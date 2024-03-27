<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel;

use Klevu\FrontendApi\Service\Provider\Cookie\CookieDurationProviderInterface;
use Klevu\FrontendApi\Service\Provider\Cookie\CookiePathProviderInterface;
use Klevu\FrontendApi\ViewModel\CookieInterface;

class Cookie implements CookieInterface
{
    /**
     * @var CookieDurationProviderInterface
     */
    private readonly CookieDurationProviderInterface $cookieDurationProvider;
    /**
     * @var CookiePathProviderInterface
     */
    private readonly CookiePathProviderInterface $cookiePathProvider;

    /**
     * @param CookieDurationProviderInterface $cookieDurationProvider
     * @param CookiePathProviderInterface $cookiePathProvider
     */
    public function __construct(
        CookieDurationProviderInterface $cookieDurationProvider,
        CookiePathProviderInterface $cookiePathProvider,
    ) {
        $this->cookieDurationProvider = $cookieDurationProvider;
        $this->cookiePathProvider = $cookiePathProvider;
    }

    /**
     * @return int
     */
    public function getCookieLifetime(): int
    {
        return $this->cookieDurationProvider->get();
    }

    /**
     * @return string
     */
    public function getCookiePath(): string
    {
        return $this->cookiePathProvider->get();
    }
}
