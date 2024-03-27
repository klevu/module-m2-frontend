<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Sdk\UserAgent\SystemInformation;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified
use Composer\InstalledVersions;
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;

class FrontendProvider implements UserAgentProviderInterface
{
    public const PRODUCT_NAME = 'klevu-m2-frontend';

    /**
     * @return string
     */
    public function execute(): string
    {
        try {
            $version = InstalledVersions::getVersion('klevu/module-m2-frontend');
        } catch (\OutOfBoundsException) {
            $version = null;
        }

        return $version
            ? sprintf('%s/%s', static::PRODUCT_NAME, $version)
            : static::PRODUCT_NAME;
    }
}
