<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\FrontendApi\Service\Provider\Customer\SessionStoragePropertiesProviderInterface;
use Klevu\FrontendApi\ViewModel\CustomerGroupSuffixInterface;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Directory\Model\Currency;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State as AppState;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class CustomerGroupSuffix implements CustomerGroupSuffixInterface
{
    public const CUSTOMER_GROUP_ID_PREFIX = 'grp_';

    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;
    /**
     * @var ScopeConfigInterface
     */
    private readonly ScopeConfigInterface $scopeConfig;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var AppState
     */
    private readonly AppState $appState;
    /**
     * @var IsEnabledDeterminerInterface
     */
    private readonly IsEnabledDeterminerInterface $isEnabledDeterminer;
    /**
     * @var mixed[]
     */
    private array $isEnabledConditions;
    /**
     * @var SessionStoragePropertiesProviderInterface|null
     */
    private readonly ?SessionStoragePropertiesProviderInterface $sessionStoragePropertiesProvider;

    /**
     * @param ScopeProviderInterface $scopeProvider
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     * @param AppState $appState
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param mixed[] $isEnabledConditions
     * @param SessionStoragePropertiesProviderInterface|null $sessionStoragePropertiesProvider
     */
    public function __construct(
        ScopeProviderInterface $scopeProvider,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        AppState $appState,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        array $isEnabledConditions = [],
        ?SessionStoragePropertiesProviderInterface $sessionStoragePropertiesProvider = null,
    ) {
        $this->scopeProvider = $scopeProvider;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->appState = $appState;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->isEnabledConditions = $isEnabledConditions;
        $this->sessionStoragePropertiesProvider = $sessionStoragePropertiesProvider
            ?: ObjectManager::getInstance()->get(SessionStoragePropertiesProviderInterface::class);
    }

    /**
     * @return bool
     * @throws InvalidIsEnabledDeterminerException
     */
    public function isEnabled(): bool
    {
        $return = false;
        try {
            $this->isEnabledDeterminer->executeAnd($this->isEnabledConditions);
            $return = true;
        } catch (InvalidIsEnabledDeterminerException $exception) {
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
        } catch (OutputDisabledException) { //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            // Output is disabled.
        }

        return $return;
    }

    /**
     * @return string
     */
    public function getCustomerGroupIdPrefix(): string
    {
        return self::CUSTOMER_GROUP_ID_PREFIX;
    }

    /**
     * @return string
     */
    public function getSessionStorageKey(): string
    {
        return $this->sessionStoragePropertiesProvider->getStorageKey();
    }

    /**
     * @return string
     */
    public function getCustomerDataKey(): string
    {
        return $this->sessionStoragePropertiesProvider->getCustomerDataSectionKey();
    }

    /***
     * @return string
     */
    public function getBaseCurrencyCode(): string
    {
        $store = $this->getStore();

        return $this->scopeConfig->getValue(
            Currency::XML_PATH_CURRENCY_BASE,
            $store ? ScopeInterface::SCOPE_STORES : ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $store?->getId(),
        );
    }

    /**
     * @return int
     */
    public function getDefaultCustomerGroupId(): int
    {
        return GroupInterface::NOT_LOGGED_IN_ID;
    }

    /**
     * @return StoreInterface|null
     */
    private function getStore(): ?StoreInterface
    {
        $scope = $this->scopeProvider->getCurrentScope();

        return $scope->getScopeType() === ScopeInterface::SCOPE_STORES
            ? $scope->getScopeObject()
            : null;
    }
}
