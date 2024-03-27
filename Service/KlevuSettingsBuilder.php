<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service;

use Klevu\Frontend\Exception\InvalidCustomSettingValueException;
use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\InvalidSettingsProviderConfigurationException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\CustomSettingsBuilderInterface;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\FrontendApi\Service\KlevuSettingsBuilderInterface;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class KlevuSettingsBuilder implements KlevuSettingsBuilderInterface
{
    /**
     * @var CustomSettingsBuilderInterface
     */
    private readonly CustomSettingsBuilderInterface $customSettingsBuilder;
    /**
     * @var SerializerInterface
     */
    private readonly SerializerInterface $serializer;
    /**
     * @var AppState
     */
    private readonly AppState $appState;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var IsEnabledDeterminerInterface
     */
    private readonly IsEnabledDeterminerInterface $isEnabledDeterminer;
    /**
     * @var mixed[]
     */
    private readonly array $klevuSettings;
    /**
     * @var mixed[]
     */
    private readonly array $isEnabledConditions;

    /**
     * @param CustomSettingsBuilderInterface $customSettingsBuilder
     * @param SerializerInterface $serializer
     * @param AppState $appState
     * @param LoggerInterface $logger
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param mixed[] $klevuSettings
     * @param mixed[] $isEnabledConditions
     */
    public function __construct(
        CustomSettingsBuilderInterface $customSettingsBuilder,
        SerializerInterface $serializer,
        AppState $appState,
        LoggerInterface $logger,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        array $klevuSettings,
        array $isEnabledConditions = [],
    ) {
        $this->customSettingsBuilder = $customSettingsBuilder;
        $this->serializer = $serializer;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->klevuSettings = $klevuSettings;
        $this->isEnabledConditions = $isEnabledConditions;
    }

    /**
     * @return string|null
     * @throws InvalidSettingsProviderConfigurationException
     * @throws InvalidCustomSettingValueException
     * @throws InvalidIsEnabledDeterminerException
     */
    public function execute(): ?string
    {
        try {
            $this->isEnabledDeterminer->executeAnd($this->isEnabledConditions);
        } catch (OutputDisabledException) {
            return null;
        }
        $settings = $this->processSettingsFromDiXml($this->klevuSettings);
        $settings = $this->customSettingsBuilder->execute(
            settings: $settings,
        );

        return $this->serializer->serialize(
            data: $settings,
        );
    }

    /**
     * @param mixed[] $klevuSettings
     *
     * @return mixed[]
     * @throws InvalidSettingsProviderConfigurationException
     */
    private function processSettingsFromDiXml(array $klevuSettings): array
    {
        $data = [];
        foreach ($klevuSettings as $key => $setting) {
            try {
                $data[$key] = $this->getSettingValue($setting);
            } catch (InvalidSettingsProviderConfigurationException $exception) {
                if ($this->isProductionMode()) {
                    $this->logger->error(
                        message: 'Method: {method}, Error: {error}',
                        context: [
                            'method' => __METHOD__,
                            'error' => $exception->getMessage(),
                        ],
                    );
                    continue;
                }
                throw $exception;
            } catch (OutputDisabledException) { //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
                // Output of setting is disabled.
                // This is fine, move onto next setting.
            }
        }

        return $data;
    }

    /**
     * @param mixed $setting
     *
     * @return bool|float|int|mixed[]|string|null
     * @throws InvalidSettingsProviderConfigurationException
     * @throws OutputDisabledException
     */
    private function getSettingValue(mixed $setting): string|array|bool|int|null|float
    {
        try {
            return match (true) {
                is_array($setting) => $this->processSettingsFromDiXml(klevuSettings: $setting),
                $setting instanceof SettingsProviderInterface => $setting->get(),
                is_scalar($setting) => $setting,
            };
        } catch (\UnhandledMatchError) {
            throw new InvalidSettingsProviderConfigurationException(
                __(
                    'Invalid Settings Provided. Expected one of (%1), received %2.',
                    implode(
                        separator: ', ',
                        array: [
                            'array',
                            SettingsProviderInterface::class,
                            'scalar',
                        ],
                    ),
                    get_debug_type($setting),
                ),
            );
        }
    }

    /**
     * @return bool
     */
    private function isProductionMode(): bool
    {
        return $this->appState->getMode() === AppState::MODE_PRODUCTION;
    }
}
