<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel\Html\Head;

use Klevu\Frontend\Constants as FrontendConstants;
use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

class CustomerGroupSuffix implements ArgumentInterface
{
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
     * @var bool
     */
    private bool $isPowerUpRequired;

    /**
     * @param LoggerInterface $logger
     * @param AppState $appState
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param mixed[] $isEnabledConditions
     * @param bool $isPowerUpRequired
     */
    public function __construct(
        LoggerInterface $logger,
        AppState $appState,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        array $isEnabledConditions = [],
        bool $isPowerUpRequired = true,
    ) {
        $this->logger = $logger;
        $this->appState = $appState;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->isEnabledConditions = $isEnabledConditions;
        $this->isPowerUpRequired = $isPowerUpRequired;
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
     * @return bool
     */
    public function isPowerUpRequired(): bool
    {
        return $this->isPowerUpRequired;
    }

    /**
     * @return string
     */
    public function getCustomerDataLoadedEventName(): string
    {
        return FrontendConstants::JS_EVENTNAME_CUSTOMER_DATA_LOADED;
    }

    /**
     * @return string
     */
    public function getCustomerDataLoadErrorEventName(): string
    {
        return FrontendConstants::JS_EVENTNAME_CUSTOMER_DATA_LOAD_ERROR;
    }
}
