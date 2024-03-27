<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Urls;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\Provider\Urls\JsUrlProvider;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Service\Provider\Urls\JsUrlProvider
 */
class JsUrlProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use WebsiteTrait;

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

        $this->implementationFqcn = JsUrlProvider::class;
        $this->interfaceFqcn = SettingsProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->get(WebsiteFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->websiteFixturesPool->rollback();
        $this->storeFixturesPool->rollback();
    }

    public function testGet_ReturnsBaseJsUrl_WhenNotSet(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject();
        $jsUrl = $provider->get();

        $this->assertSame(expected: 'https://js.klevu.com', actual: $jsUrl);
    }

    /**
     * @magentoConfigFixture default/klevu_configuration/developer/url_js global.url
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_js store.url
     */
    public function testGet_ReturnsStoreJSUrl_AtStoreScope(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject();
        $jsUrl = $provider->get();

        $this->assertSame(expected: 'https://store.url', actual: $jsUrl);
    }

    /**
     * @magentoConfigFixture default/klevu_configuration/developer/url_js global.url
     * @magentoConfigFixture klevu_test_website_1_website klevu_configuration/developer/url_js website.url
     */
    public function testGet_ReturnsWebsiteJSUrl_AtWebsiteScope(): void
    {
        $this->createWebsite();
        $website = $this->websiteFixturesPool->get('test_website');
        $this->createStore([
            'website_id' => $website->getId(),
        ]);
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($website->get());

        $provider = $this->instantiateTestObject();
        $jsUrl = $provider->get();

        $this->assertSame(expected: 'https://website.url', actual: $jsUrl);
    }
}
