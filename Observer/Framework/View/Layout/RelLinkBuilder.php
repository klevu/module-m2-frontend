<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Observer\Framework\View\Layout;

use Klevu\FrontendApi\Service\Provider\Layout\RelLinkProviderInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;

class RelLinkBuilder implements ObserverInterface
{
    /**
     * @var PageConfig
     */
    private readonly PageConfig $pageConfig;
    /**
     * @var RelLinkProviderInterface
     */
    private readonly RelLinkProviderInterface $relLinkProvider;

    /***
     * @param PageConfig $pageConfig
     * @param RelLinkProviderInterface $relLinkProvider
     */
    public function __construct(
        PageConfig $pageConfig,
        RelLinkProviderInterface $relLinkProvider,
    ) {
        $this->pageConfig = $pageConfig;
        $this->relLinkProvider = $relLinkProvider;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(
        Observer $observer, //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        foreach ($this->relLinkProvider->get() as $link) {
            $attributes = ['rel' => $link[RelLinkProviderInterface::RESOURCE_TYPE]];
            if ($link[RelLinkProviderInterface::RESOURCE_AS] ?? null) {
                $attributes['as'] = $link[RelLinkProviderInterface::RESOURCE_AS];
            }

            $this->pageConfig->addRemotePageAsset(
                url: $link[RelLinkProviderInterface::RESOURCE_PATH],
                contentType: 'link_rel',
                properties: [
                    'attributes' => $attributes,
                ],
            );
        }
    }
}
