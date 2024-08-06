<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\Frontend\Exception\InvalidDelayPowerUpDeterminerException;
use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\DelayPowerUpCondition\DelayPowerUpConditionInterface;
use Klevu\FrontendApi\Service\DelayPowerUpDeterminerInterface;
use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Psr\Log\LoggerInterface;

class PowerUpProvider implements SettingsProviderInterface
{
    /**
     * @var DelayPowerUpDeterminerInterface
     */
    private readonly DelayPowerUpDeterminerInterface $delayPowerUpDeterminer;
    /**
     * @var IsEnabledDeterminerInterface
     */
    private readonly IsEnabledDeterminerInterface $isEnabledDeterminer;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var mixed[]
     */
    private readonly array $delayPowerUpConditions;
    /**
     * @var mixed[]
     */
    private readonly array $isEnabledConditions;

    /**
     * @param DelayPowerUpDeterminerInterface $delayPowerUpDeterminer
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param LoggerInterface $logger
     * @param DelayPowerUpConditionInterface[] $delayPowerUpConditions
     * @param IsEnabledConditionInterface[] $isEnabledConditions
     */
    public function __construct(
        DelayPowerUpDeterminerInterface $delayPowerUpDeterminer,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        LoggerInterface $logger,
        array $delayPowerUpConditions = [],
        array $isEnabledConditions = [],
    ) {
        $this->delayPowerUpDeterminer = $delayPowerUpDeterminer;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->logger = $logger;
        $this->delayPowerUpConditions = $delayPowerUpConditions;
        $this->isEnabledConditions = $isEnabledConditions;
    }

    /**
     * @return false
     * @throws OutputDisabledException
     */
    public function get(): bool
    {
        try {
            $this->isEnabledDeterminer->executeAnd($this->isEnabledConditions);
            $this->delayPowerUpDeterminer->executeAnd(
                delayPowerUpConditions: $this->delayPowerUpConditions,
            );
        } catch (InvalidDelayPowerUpDeterminerException | InvalidIsEnabledDeterminerException $exception) {
            $this->logger->error(
                message: 'Method: {method}, Error: {message}',
                context: [
                    'method' => __METHOD__,
                    'message' => $exception->getMessage(),
                ],
            );
        }

        // Let JSv2 handle the default value of true.
        // Only set powerUp when it needs to be deferred.
        // Throwing OutputDisabledException will cause \Klevu\Frontend\Service\KlevuSettingsBuilder
        // to skip to the next setting.
        return false;
    }
}
