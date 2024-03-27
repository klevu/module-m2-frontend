<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\Frontend\Exception\InvalidCustomSettingValueException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource;
use Klevu\Frontend\Service\Provider\CustomSettingProviderFactory;
use Klevu\Frontend\Service\Provider\ScopeConfigSettingsProviderFactory;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Psr\Log\LoggerInterface;

class SettingsProvider implements SettingsProviderInterface
{
    /**
     * @var ScopeConfigSettingsProviderFactory
     */
    private readonly ScopeConfigSettingsProviderFactory $scopeConfigSettingsProviderFactory;
    /**
     * @var CustomSettingProviderFactory
     */
    private readonly CustomSettingProviderFactory $customSettingProviderFactory;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var string|null
     */
    private readonly ?string $configSettingPath;
    /**
     * @var string|null
     */
    private readonly ?string $customSettingsPath;
    /**
     * @var string|null
     */
    private readonly ?string $returnType;

    /**
     * @param ScopeConfigSettingsProviderFactory $scopeConfigSettingsProviderFactory
     * @param CustomSettingProviderFactory $customSettingProviderFactory
     * @param LoggerInterface $logger
     * @param string|null $configSettingPath
     * @param string|null $customSettingsPath
     * @param string|null $returnType
     */
    public function __construct(
        ScopeConfigSettingsProviderFactory $scopeConfigSettingsProviderFactory,
        CustomSettingProviderFactory $customSettingProviderFactory,
        LoggerInterface $logger,
        ?string $configSettingPath = null,
        ?string $customSettingsPath = null,
        ?string $returnType = null,
    ) {
        $this->scopeConfigSettingsProviderFactory = $scopeConfigSettingsProviderFactory;
        $this->customSettingProviderFactory = $customSettingProviderFactory;
        $this->logger = $logger;
        $this->configSettingPath = $configSettingPath;
        $this->customSettingsPath = $customSettingsPath;
        $this->returnType = $returnType;
    }

    /**
     * @return bool|int|string|null
     * @throws OutputDisabledException
     */
    public function get(): bool|int|string|null
    {
        $customSetting = null;
        try {
            $customSetting = $this->getCustomSetting();
        } catch (InvalidCustomSettingValueException $exception) {
            $this->logger->error(
                message: 'Method: {method}, Error {error}',
                context: [
                    'method' => __METHOD__,
                    'error' => $exception->getMessage(),
                ],
            );
        }

        return $customSetting ?? $this->getCoreConfigSetting();
    }

    /**
     * @return bool|int|string|null
     * @throws InvalidCustomSettingValueException
     */
    private function getCustomSetting(): bool|int|string|null
    {
        if (null === $this->customSettingsPath) {
            return null;
        }
        $provider = $this->customSettingProviderFactory->create([
            'customSettingsPath' => $this->customSettingsPath,
        ]);
        $return = $provider->get();
        if (null !== $return) {
            $this->validateReturnType($return);
        }

        return $return;
    }

    /**
     * @param mixed $value
     *
     * @return void
     * @throws InvalidCustomSettingValueException
     */
    private function validateReturnType(mixed $value): void
    {
        if (null === $this->returnType) {
            return;
        }
        $castValue = match ($this->returnType) {
            KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN => (bool)$value,
            KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER => (int)$value,
            KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING => (string)$value,
            default => null,
        };
        if ($castValue === $value) {
            return;
        }

        throw new InvalidCustomSettingValueException(
            __(
                'Invalid Type set for path %1 in Jsv2 Custom Settings. Expected %2, received %3.',
                $this->customSettingsPath,
                KlevuCustomOptionsTypeSource::getLabel($this->returnType),
                get_debug_type($value),
            ),
        );
    }

    /**
     * @return bool|int|string|null
     * @throws OutputDisabledException
     */
    private function getCoreConfigSetting(): bool|int|string|null
    {
        if (!$this->configSettingPath) {
            return null;
        }
        /** @var SettingsProviderInterface $provider */
        $provider = $this->scopeConfigSettingsProviderFactory->create([
            'path' => $this->configSettingPath,
            'returnType' => $this->returnType,
        ]);

        return $provider->get();
    }
}
