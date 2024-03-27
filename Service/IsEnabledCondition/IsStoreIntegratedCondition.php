<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\IsEnabledCondition;

use Klevu\Configuration\Service\IsStoreIntegratedServiceInterface;
use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;

class IsStoreIntegratedCondition implements IsEnabledConditionInterface
{
    /**
     * @var IsStoreIntegratedServiceInterface
     */
    private readonly IsStoreIntegratedServiceInterface $isStoreIntegratedService;

    /**
     * @param IsStoreIntegratedServiceInterface $isStoreIntegratedService
     */
    public function __construct(IsStoreIntegratedServiceInterface $isStoreIntegratedService)
    {
        $this->isStoreIntegratedService = $isStoreIntegratedService;
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        return $this->isStoreIntegratedService->execute();
    }
}
