<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service;

use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Magento\Framework\App\State as AppState;
use Psr\Log\LoggerInterface;

class IsEnabledDeterminer implements IsEnabledDeterminerInterface
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
     * @param mixed[] $isEnabledConditions
     *
     * @return void
     * @throws InvalidIsEnabledDeterminerException
     * @throws OutputDisabledException
     */
    public function executeAnd(array $isEnabledConditions): void
    {
        $this->execute(
            isEnabledConditions: $isEnabledConditions,
            allConditionMustBeEnabled: true,
        );
    }

    /**
     * @param mixed[] $isEnabledConditions
     *
     * @return void
     * @throws InvalidIsEnabledDeterminerException
     * @throws OutputDisabledException
     */
    public function executeOr(array $isEnabledConditions): void
    {
        $this->execute(
            isEnabledConditions: $isEnabledConditions,
            allConditionMustBeEnabled: false,
        );
    }

    /**
     * @param mixed[] $isEnabledConditions
     * @param bool|null $allConditionMustBeEnabled
     *
     * @return void
     * @throws InvalidIsEnabledDeterminerException
     * @throws OutputDisabledException
     */
    private function execute(array $isEnabledConditions, ?bool $allConditionMustBeEnabled = true): void
    {
        if (!$isEnabledConditions) {
            return;
        }
        foreach ($isEnabledConditions as $key => $isEnabledCondition) {
            switch (true) {
                case is_array($isEnabledCondition):
                    $this->executeOr(isEnabledConditions: $isEnabledCondition);

                    return;
                case $isEnabledCondition instanceof IsEnabledConditionInterface:
                    $isEnabled = $isEnabledCondition->execute();
                    if (!$isEnabled && $allConditionMustBeEnabled) {
                        throw new OutputDisabledException(
                            __('Condition "%1" is disabled', (string)$key),
                        );
                    }
                    if ($isEnabled && !$allConditionMustBeEnabled) {
                        return;
                    }
                    break;
                default:
                    $this->handleInvalidCondition((string)$key, $isEnabledCondition);
                    break;
            }
        }
        if (!$allConditionMustBeEnabled) {
            throw new OutputDisabledException(
                __('All conditions are disabled'),
            );
        }
    }

    /**
     * @param string $key
     * @param mixed $isEnabledCondition
     *
     * @return void
     * @throws InvalidIsEnabledDeterminerException
     */
    private function handleInvalidCondition(string $key, mixed $isEnabledCondition): void
    {
        $message = __(
            'IsEnabledCondition "%1" must be instance of %2; %3 received',
            $key,
            IsEnabledConditionInterface::class,
            get_debug_type($isEnabledCondition),
        );
        if ($this->appState->getMode() !== AppState::MODE_PRODUCTION) {
            throw new InvalidIsEnabledDeterminerException($message);
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
