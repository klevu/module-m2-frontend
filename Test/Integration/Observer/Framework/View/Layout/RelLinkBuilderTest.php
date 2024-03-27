<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Observer\Framework\View\Layout;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Observer\Framework\View\Layout\RelLinkBuilder;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Asset\AssetInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Frontend\Observer\Framework\View\Layout\RelLinkBuilder
 * @magentoAppArea frontend
 */
class RelLinkBuilderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;

    private const OBSERVER_NAME = 'Klevu_Frontend_framework_view_layout_rel_link_builder';
    private const EVENT_NAME = 'layout_generate_blocks_after';

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = RelLinkBuilder::class;
        $this->interfaceFqcn = ObserverInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->storeFixturesPool->rollback();
    }

    public function testRelLinkBuilderObserver_IsConfigured(): void
    {
        $observerConfig = $this->objectManager->create(type: EventConfig::class);
        $observers = $observerConfig->getObservers(eventName: self::EVENT_NAME);

        $this->assertArrayHasKey(key: self::OBSERVER_NAME, array: $observers);
        $this->assertSame(
            expected: ltrim(string: RelLinkBuilder::class, characters: '\\'),
            actual: $observers[self::OBSERVER_NAME]['instance'],
        );
    }

    public function testExecute_AddsRemotePageAssets(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );

        $pageConfig = $this->objectManager->get(type: PageConfig::class);
        $assetCollection = $pageConfig->getAssetCollection();
        $assets = $assetCollection->getAll();
        $this->assertCount(expectedCount: 0, haystack: $assets);

        $this->dispatchEvent(
            request: $this->objectManager->get(type: RequestInterface::class),
            layout: $this->objectManager->get(type: LayoutInterface::class),
        );

        $pageConfig = $this->objectManager->get(type: PageConfig::class);
        $assetCollection = $pageConfig->getAssetCollection();
        $assets = $assetCollection->getAll();

        $this->assertArrayHasKey(key: 'https://js.klevu.com', array: $assets);
        $asset = $assets['https://js.klevu.com'];
        $this->assertInstanceOf(expected: AssetInterface::class, actual: $asset);
        $this->assertSame(expected: 'https://js.klevu.com', actual: $asset->getUrl());
        $this->assertSame(expected: 'link_rel', actual: $asset->getContentType());
    }

    /**
     * @param RequestInterface $request
     * @param LayoutInterface $layout
     *
     * @return void
     */
    private function dispatchEvent(RequestInterface $request, LayoutInterface $layout): void
    {
        /** @var EventManager $eventManager */
        $eventManager = $this->objectManager->get(type: EventManager::class);

        $fullActionName = method_exists($request, 'getFullActionName')
            ? $request->getFullActionName()
            : null;

        $eventManager->dispatch(
            self::EVENT_NAME,
            [
                'full_action_name' => $fullActionName,
                'layout' => $layout,
            ],
        );
    }
}
