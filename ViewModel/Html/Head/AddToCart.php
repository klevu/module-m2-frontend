<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\ViewModel\Html\Head;

use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\FrontendApi\Service\Provider\Urls\AddToCartUrlProviderInterface;
use Klevu\FrontendApi\ViewModel\Html\Head\AddToCartInterface;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class AddToCart implements AddToCartInterface
{
    /**
     * @var AddToCartUrlProviderInterface
     */
    private readonly AddToCartUrlProviderInterface $addToCartUrlProvider;
    /**
     * @var FormKey
     */
    private readonly FormKey $formKey;
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
    private readonly array $isEnabledConditions;

    /**
     * @param AddToCartUrlProviderInterface $addToCartUrlProvider
     * @param FormKey $formKey
     * @param AppState $appState
     * @param LoggerInterface $logger
     * @param IsEnabledDeterminerInterface $isEnabledDeterminer
     * @param mixed[] $isEnabledConditions
     */
    public function __construct(
        AddToCartUrlProviderInterface $addToCartUrlProvider,
        FormKey $formKey,
        AppState $appState,
        LoggerInterface $logger,
        IsEnabledDeterminerInterface $isEnabledDeterminer,
        array $isEnabledConditions = [],
    ) {
        $this->addToCartUrlProvider = $addToCartUrlProvider;
        $this->formKey = $formKey;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->isEnabledDeterminer = $isEnabledDeterminer;
        $this->isEnabledConditions = $isEnabledConditions;
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
     * @param int|null $productId
     *
     * @return string
     */
    public function getAddToCartUrl(?int $productId = null): string
    {
        return $this->addToCartUrlProvider->get($productId);
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }
}
