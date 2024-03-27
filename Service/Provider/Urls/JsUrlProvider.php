<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Urls;

use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\PhpSDK\Provider\BaseUrlsProviderInterface;

class JsUrlProvider implements SettingsProviderInterface
{
    /**
     * @var BaseUrlsProviderInterface
     */
    private readonly BaseUrlsProviderInterface $baseUrlsProvider;

    /**
     * @param BaseUrlsProviderInterface $baseUrlsProvider
     */
    public function __construct(BaseUrlsProviderInterface $baseUrlsProvider)
    {
        $this->baseUrlsProvider = $baseUrlsProvider;
    }

    /**
     * @return string
     */
    public function get(): string
    {
        return 'https://' . $this->baseUrlsProvider->getJsUrl();
    }
}
