<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Exception\InvalidCustomSettingValueException;
use Klevu\FrontendApi\Service\Provider\CustomSettingsProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;

class CustomSettingsProvider implements CustomSettingsProviderInterface
{
    private const XML_PATH_CUSTOM_SETTINGS = 'klevu_frontend/general/klevu_settings';

    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var SerializerInterface
     */
    private readonly SerializerInterface $serializer;
    /**
     * @var mixed[][]
     */
    private array $data = [];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeProviderInterface $storeScopeProvider
     * @param SerializerInterface $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ScopeProviderInterface $storeScopeProvider,
        SerializerInterface $serializer,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->scopeProvider = $storeScopeProvider;
        $this->serializer = $serializer;
    }

    /**
     * @return string[][]
     * @throws InvalidCustomSettingValueException
     */
    public function get(): array
    {
        $scope = $this->scopeProvider->getCurrentScope();
        if (isset($this->data[$scope->getScopeType()][$scope->getScopeId()])) {
            return $this->data[$scope->getScopeType()][$scope->getScopeId()];
        }
        $settings = $this->scopeConfig->getValue(
            self::XML_PATH_CUSTOM_SETTINGS,
            $scope->getScopeType(),
            $scope->getScopeId(),
        );
        if ($settings) {
            try {
                $this->data[$scope->getScopeType()][$scope->getScopeId()] = $this->serializer->unserialize(
                    string: $settings,
                );
            } catch (\InvalidArgumentException $exception) {
                throw new InvalidCustomSettingValueException(
                    __(
                        'The data stored in %1 could not be unserialized. Method: %2. Error: %3',
                        self::XML_PATH_CUSTOM_SETTINGS,
                        __METHOD__,
                        $exception->getMessage(),
                    ),
                );
            }
        }

        return $this->data[$scope->getScopeType()][$scope->getScopeId()] ?? [];
    }
}
