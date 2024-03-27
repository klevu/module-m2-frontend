<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel\Html\Head;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\ViewModel\Html\Head\JsIncludes;
use Klevu\Frontend\ViewModel\Html\Head\JsIncludesCore as JsIncludesCoreVirtualType;
use Klevu\FrontendApi\ViewModel\JsIncludesInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Frontend\ViewModel\Html\Head\JsIncludesCore
 * @magentoAppArea frontend
 */
class JsIncludesCoreTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;

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

        $this->implementationFqcn = JsIncludesCoreVirtualType::class; // @phpstan-ignore-line
        $this->interfaceFqcn = JsIncludesInterface::class;
        $this->implementationForVirtualType = JsIncludes::class;
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

    public function testLinkIsRemoved_WhenStoreNotIntegrated(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject();
        $links = $viewModel->getLinks();

        $this->assertCount(expectedCount: 0, haystack: $links);
    }

    public function testLinkIsRemoved_WhenOnlyJsApiKeyPresent(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
        );

        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject();
        $links = $viewModel->getLinks();

        $this->assertCount(expectedCount: 0, haystack: $links);
    }

    public function testLinkIsRemoved_WhenOnlyRestAuthKeyPresent(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            restAuthKey: 'klevu-rest-key',
        );

        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject();
        $links = $viewModel->getLinks();

        $this->assertCount(expectedCount: 0, haystack: $links);
    }

    public function testLinkIsPresent_WhenStoreIntegrated(): void
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

        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject();
        $links = $viewModel->getLinks();

        $this->assertCount(expectedCount: 1, haystack: $links);
        $firstLink = $links[array_key_first($links)] ?? '';
        $this->assertStringContainsString(needle: 'https://', haystack: $firstLink);
        $this->assertStringContainsString(needle: 'core/v2/klevu.js', haystack: $firstLink);
    }
}
