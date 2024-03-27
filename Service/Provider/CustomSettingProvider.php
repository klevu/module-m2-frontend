<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\Frontend\Block\Adminhtml\Form\Field\CustomSettings;
use Klevu\Frontend\Exception\InvalidCustomSettingValueException;
use Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource;
use Klevu\FrontendApi\Service\Provider\CustomSettingProviderInterface;
use Klevu\FrontendApi\Service\Provider\CustomSettingsProviderInterface;

class CustomSettingProvider implements CustomSettingProviderInterface
{
    /**
     * @var CustomSettingsProviderInterface
     */
    private readonly CustomSettingsProviderInterface $customSettingsProvider;
    /**
     * @var string
     */
    private readonly string $customSettingsPath;

    /**
     * @param CustomSettingsProviderInterface $customSettingsProvider
     * @param string $customSettingsPath
     */
    public function __construct(
        CustomSettingsProviderInterface $customSettingsProvider,
        string $customSettingsPath,
    ) {
        $this->customSettingsProvider = $customSettingsProvider;
        $this->customSettingsPath = $customSettingsPath;
    }

    /**
     * @return bool|int|string|null
     * @throws InvalidCustomSettingValueException
     */
    public function get(): bool|int|string|null
    {
        $customSetting = $this->getCustomSetting();

        return $customSetting
            ? $this->castToType($customSetting)
            : null;
    }

    /**
     * @return mixed[]|null
     * @throws InvalidCustomSettingValueException
     */
    private function getCustomSetting(): ?array
    {
        $filteredCustomSettings = array_filter(
            array: $this->customSettingsProvider->get(),
            callback: fn ($setting) => (
                $setting[CustomSettings::COLUMN_NAME_PATH] === $this->customSettingsPath
            ),
        );

        return array_shift($filteredCustomSettings);
    }

    /**
     * @param mixed[] $customSetting
     *
     * @return bool|int|string|null
     */
    private function castToType(array $customSetting): string|int|bool|null
    {
        $customSettingType = $customSetting[CustomSettings::COLUMN_NAME_TYPE] ?? null;
        $customSettingValue = $customSetting[CustomSettings::COLUMN_NAME_VALUE] ?? null;

        return match ($customSettingType) {
            KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN => $this->getBooleanValue(value: $customSettingValue),
            KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER => (int)$customSettingValue,
            KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING => (string)$customSettingValue,
            default => null,
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
