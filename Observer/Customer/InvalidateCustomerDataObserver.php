<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Observer\Customer;

use Klevu\FrontendApi\Service\Action\SetCookieActionInterface;
use Klevu\FrontendApi\Service\Provider\CookieProviderInterface;
use Klevu\FrontendApi\Service\Provider\Customer\CookiePropertiesProviderInterface as CustomerCookieProviderInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class InvalidateCustomerDataObserver implements ObserverInterface
{
    /**
     * @var CookieProviderInterface
     */
    private readonly CookieProviderInterface $cookieProvider;
    /**
     * @var SetCookieActionInterface
     */
    private readonly SetCookieActionInterface $setCookieAction;
    /**
     * @var CustomerCookieProviderInterface
     */
    private readonly CustomerCookieProviderInterface $customerCookieProvider;

    /**
     * @param CookieProviderInterface $cookieProvider
     * @param SetCookieActionInterface $setCookieAction
     * @param CustomerCookieProviderInterface $customerCookieProvider
     */
    public function __construct(
        CookieProviderInterface $cookieProvider,
        SetCookieActionInterface $setCookieAction,
        CustomerCookieProviderInterface $customerCookieProvider,
    ) {
        $this->cookieProvider = $cookieProvider;
        $this->setCookieAction = $setCookieAction;
        $this->customerCookieProvider = $customerCookieProvider;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(
        Observer $observer, //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        $cookieKey = $this->customerCookieProvider->getCookieKey();
        $originalCookieData = $this->cookieProvider->get(
            name: $cookieKey,
        );
        $updatedCookieData = $this->getInvalidateCustomerData($originalCookieData);

        $this->setCookieAction->execute(
            name: $cookieKey,
            data: $updatedCookieData,
        );
    }

    /**
     * @param mixed[] $cookieData
     *
     * @return mixed[]
     */
    private function getInvalidateCustomerData(array $cookieData): array
    {
        $expireSections = $this->customerCookieProvider->getExpireSectionsKey();
        $customerDataSection = $this->customerCookieProvider->getCustomerDataSectionKey();

        if (!is_array($cookieData[$expireSections] ?? null)) {
            $cookieData[$expireSections] = [];
        }
        $cookieData[$expireSections][$customerDataSection] = -1;

        return $cookieData;
    }
}
