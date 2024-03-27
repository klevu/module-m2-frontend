<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service;

use Klevu\Frontend\Block\Adminhtml\Form\Field\CustomSettings;
use Klevu\Frontend\Exception\InvalidCustomSettingValueException;
use Klevu\Frontend\Exception\InvalidSettingsProviderConfigurationException;
use Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource;
use Klevu\FrontendApi\Service\CustomSettingsBuilderInterface;
use Klevu\FrontendApi\Service\Provider\CustomSettingsProviderInterface;
use Magento\Framework\App\State as AppState;
use Psr\Log\LoggerInterface;

class CustomSettingsBuilder implements CustomSettingsBuilderInterface
{
    /**
     * @var CustomSettingsProviderInterface
     */
    private readonly CustomSettingsProviderInterface $customSettingsProvider;
    /**
     * @var AppState
     */
    private readonly AppState $appState;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @param CustomSettingsProviderInterface $customSettingsProvider
     * @param AppState $appState
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomSettingsProviderInterface $customSettingsProvider,
        AppState $appState,
        LoggerInterface $logger,
    ) {
        $this->customSettingsProvider = $customSettingsProvider;
        $this->appState = $appState;
        $this->logger = $logger;
    }

    /**
     * @param mixed[]|null $settings
     *
     * @return mixed[]
     * @throws InvalidSettingsProviderConfigurationException
     * @throws InvalidCustomSettingValueException
     */
    public function execute(?array $settings = []): array
    {
        $customSettings = [];
        try {
            $customSettings = $this->customSettingsProvider->get();
        } catch (InvalidCustomSettingValueException $exception) {
            if ($this->appState->getMode() !== AppState::MODE_PRODUCTION) {
                throw $exception;
            }
            $this->logger->error(
                message: 'Method: {method}, Error: {error}',
                context: [
                    'method' => __METHOD__,
                    'error' => $exception->getMessage(),
                ],
            );
        }
        foreach ($customSettings as $customSetting) {
            try {
                $settings = $this->addCustomSettingToDataRecursively(
                    settings: $settings,
                    pathToValue: explode('.', $customSetting[CustomSettings::COLUMN_NAME_PATH] ?? ''),
                    value: $this->getValue($customSetting),
                );
            } catch (InvalidSettingsProviderConfigurationException $exception) {
                if ($this->appState->getMode() !== AppState::MODE_PRODUCTION) {
                    throw $exception;
                }
                $this->logger->error(
                    message: 'Method: {method}, Error: {error}',
                    context: [
                        'method' => __METHOD__,
                        'error' => $exception->getMessage(),
                    ],
                );
            }
        }

        return $settings;
    }

    /**
     * @param mixed[] $settings
     * @param string[] $pathToValue
     * @param bool|int|string $value
     *
     * @return mixed[]
     */
    private function addCustomSettingToDataRecursively(
        array $settings,
        array $pathToValue,
        bool|int|string $value,
    ): array {
        $key = array_shift($pathToValue);
        $settings[$key] = $pathToValue
            ? $this->addCustomSettingToDataRecursively(
                settings: $settings[$key] ?? [],
                pathToValue: $pathToValue,
                value: $value,
            )
            : $value;

        return $settings;
    }

    /**
     * @param mixed $setting
     *
     * @return bool|int|string
     * @throws InvalidSettingsProviderConfigurationException
     */
    private function getValue(mixed $setting): string|int|bool
    {
        $type = $setting[CustomSettings::COLUMN_NAME_TYPE] ?? null;
        try {
            return match ($type) {
                KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN => $this->getBooleanValue(
                    value: $setting[CustomSettings::COLUMN_NAME_VALUE],
                ),
                KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER => (int)$setting[CustomSettings::COLUMN_NAME_VALUE],
                KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING => (string)$setting[CustomSettings::COLUMN_NAME_VALUE],
            };
        } catch (\UnhandledMatchError) {
            throw new InvalidSettingsProviderConfigurationException(
                __(
                    'Invalid setting type provided. Expected one of %1, received %2',
                    implode(', ', [
                        KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
                        KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER,
                        KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
                    ]),
                    $type,
                ),
            );
        }
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
