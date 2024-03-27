<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ScopeConfigSettingsProvider implements SettingsProviderInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var string
     */
    private readonly string $path;
    /**
     * @var string|null
     */
    private readonly ?string $returnType;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ScopeProviderInterface $scopeProvider
     * @param string $path
     * @param string|null $returnType
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ScopeProviderInterface $scopeProvider,
        string $path,
        ?string $returnType = null,
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->scopeProvider = $scopeProvider;
        $this->path = $path;
        $this->returnType = $returnType;
    }

    /**
     * @return bool|int|string|null
     */
    public function get(): bool|int|string|null
    {
        $scope = $this->scopeProvider->getCurrentScope();
        $value = $this->scopeConfig->getValue(
            $this->path,
            $scope->getScopeType(),
            $scope->getScopeId(),
        );

        return match ($this->returnType) {
            KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN => $this->getBooleanValue($value),
            KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER => (int)$value,
            KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING => (string)$value,
            default => $value,
        };
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function getBooleanValue(mixed $value): bool
    {
        $value = is_string($value)
            ? trim($value)
            : $value;

        return 'false' !== $value && $value;
    }
}
