<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;

class LanguageProvider implements SettingsProviderInterface
{
    /**
     * @var SettingsProviderInterface
     */
    private readonly SettingsProviderInterface $localeCodeProvider;

    /**
     * @param SettingsProviderInterface $localeCodeProvider
     */
    public function __construct(SettingsProviderInterface $localeCodeProvider)
    {
        $this->localeCodeProvider = $localeCodeProvider;
    }

    /**
     * @return string
     * @throws OutputDisabledException
     */
    public function get(): string
    {
        return substr(
            string: $this->localeCodeProvider->get(),
            offset: 0,
            length: 2,
        );
    }
}
