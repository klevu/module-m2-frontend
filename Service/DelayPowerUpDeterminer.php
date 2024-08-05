<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service;

use Klevu\Frontend\Exception\InvalidDelayPowerUpDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\DelayPowerUpCondition\DelayPowerUpConditionInterface;
use Klevu\FrontendApi\Service\DelayPowerUpDeterminerInterface;
use Magento\Framework\App\State as AppState;
use Psr\Log\LoggerInterface;

class DelayPowerUpDeterminer implements DelayPowerUpDeterminerInterface
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
     * @param LoggerInterface $logger
     * @param AppState $appState
     */
    public function __construct(
        LoggerInterface $logger,
        AppState $appState,
    ) {
        $this->logger = $logger;
        $this->appState = $appState;
    }

    /**
     * @param array<DelayPowerUpConditionInterface|DelayPowerUpConditionInterface[]> $delayPowerUpConditions
     *
     * @return void
     * @throws InvalidDelayPowerUpDeterminerException
     * @throws OutputDisabledException
     */
    public function executeAnd(array $delayPowerUpConditions): void
    {
        $this->execute(
            delayPowerUpConditions: $delayPowerUpConditions,
            allConditionsMustBeMet: true,
        );
    }

    /**
     * @param array<DelayPowerUpConditionInterface|DelayPowerUpConditionInterface[]> $delayPowerUpConditions
     *
     * @return void
     * @throws InvalidDelayPowerUpDeterminerException
     * @throws OutputDisabledException
     */
    public function executeOr(array $delayPowerUpConditions): void
    {
        $this->execute(
            delayPowerUpConditions: $delayPowerUpConditions,
            allConditionsMustBeMet: false,
        );
    }

    /**
     * @param array<DelayPowerUpConditionInterface|DelayPowerUpConditionInterface[]> $delayPowerUpConditions
     * @param bool $allConditionsMustBeMet
     *
     * @return void
     * @throws InvalidDelayPowerUpDeterminerException
     * @throws OutputDisabledException
     */
    private function execute(array $delayPowerUpConditions, bool $allConditionsMustBeMet): void
    {
        if (!$delayPowerUpConditions) {
            return;
        }
        foreach ($delayPowerUpConditions as $key => $delayPowerUpCondition) {
            switch (true) {
                case is_array($delayPowerUpCondition):
                    $this->executeOr(delayPowerUpConditions: $delayPowerUpCondition);

                    return;
                case $delayPowerUpCondition instanceof DelayPowerUpConditionInterface:
                    $isDelayRequired = $delayPowerUpCondition->execute();
                    if (!$isDelayRequired && $allConditionsMustBeMet) {
                        throw new OutputDisabledException(
                            __('Condition "%1" is not met', (string)$key),
                        );
                    }
                    if ($isDelayRequired && !$allConditionsMustBeMet) {
                        return;
                    }
                    break;
                default:
                    $this->handleInvalidCondition((string)$key, $delayPowerUpCondition);
                    break;
            }
        }
        if (!$allConditionsMustBeMet) {
            throw new OutputDisabledException(
                __('All conditions are disabled'),
            );
        }
    }

    /**
     * @param string $key
     * @param DelayPowerUpConditionInterface|DelayPowerUpConditionInterface[] $delayPowerUpCondition
     *
     * @return void
     * @throws InvalidDelayPowerUpDeterminerException
     */
    private function handleInvalidCondition(string $key, mixed $delayPowerUpCondition): void
    {
        $message = __(
            'delayPowerUpCondition "%1" must be instance of %2; %3 received',
            $key,
            DelayPowerUpConditionInterface::class,
            get_debug_type($delayPowerUpCondition),
        );
        if ($this->appState->getMode() !== AppState::MODE_PRODUCTION) {
            throw new InvalidDelayPowerUpDeterminerException($message);
        }
        $this->logger->warning(
            message: 'Method: {method}, Warning: {warning}',
            context: [
                'method' => __METHOD__,
                'warning' => $message->render(),
            ],
        );
    }
}
