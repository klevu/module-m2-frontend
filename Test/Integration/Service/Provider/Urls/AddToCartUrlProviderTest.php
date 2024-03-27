<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Urls;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\Provider\Urls\AddToCartUrlProvider;
use Klevu\FrontendApi\Service\Provider\Urls\AddToCartUrlProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Url;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Service\Provider\Urls\AddToCartUrlProvider::class
 * @magentoAppArea frontend
 */
class AddToCartUrlProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;

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

        $this->implementationFqcn = AddToCartUrlProvider::class;
        $this->interfaceFqcn = AddToCartUrlProviderInterface::class;
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

    /**
     * Note: isolation flushes the URL memory cache
     * @magentoAppIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_url https://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_link_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_link_url https://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     */
    public function testGet_ReturnsAddToCartUrl_OnCategoryPage(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        /** @var HttpRequest $request */
        $request = Bootstrap::getObjectManager()->create(HttpRequest::class);
        //Emulate HTTPS request
        $request->getServer()->set('HTTPS', 'on');
        $request->getServer()->set('SERVER_PORT', 443);
        $request->setRouteName('catalog');
        $request->setControllerName('category');
        $request->setActionName('index');

        $url = $this->objectManager->create(Url::class, [
            'request' => $request,
        ]);
        $this->objectManager->addSharedInstance(
            instance: $url,
            className: Url::class,
        );

        /** @var AddToCartUrlProviderInterface $provider */
        $provider = $this->instantiateTestObject([
            'request' => $request,
        ]);
        $url = $provider->get();

        $this->assertStringContainsString(
            needle: 'https://sample.com/checkout/cart/add/uenc/',
            haystack: $url,
        );
        $this->assertStringContainsString(
            needle: '?___store=klevu_test_store_1',
            haystack: $url,
        );
    }

    /**
     * Note: isolation flushes the URL memory cache
     * @magentoAppIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_url https://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_link_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_link_url https://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     */
    public function testGet_ReturnsAddToCartUrl_OnCategoryPage_ForProduct(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        /** @var HttpRequest $request */
        $request = Bootstrap::getObjectManager()->create(HttpRequest::class);
        //Emulate HTTPS request
        $request->getServer()->set('HTTPS', 'on');
        $request->getServer()->set('SERVER_PORT', 443);
        $request->setRouteName('catalog');
        $request->setControllerName('category');
        $request->setActionName('index');

        $url = $this->objectManager->create(Url::class, [
            'request' => $request,
        ]);

        $this->objectManager->addSharedInstance(
            instance: $url,
            className: Url::class,
        );

        /** @var AddToCartUrlProviderInterface $provider */
        $provider = $this->instantiateTestObject([
            'request' => $request,
        ]);
        $url = $provider->get(productId: 1);

        $this->assertStringContainsString(
            needle: 'https://sample.com/checkout/cart/add/uenc/',
            haystack: $url,
        );
        $this->assertStringContainsString(
            needle: '/product/1',
            haystack: $url,
        );
        $this->assertStringContainsString(
            needle: '?___store=klevu_test_store_1',
            haystack: $url,
        );
    }

    /**
     * Note: isolation flushes the URL memory cache
     * @magentoAppIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_link_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_link_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     */
    public function testGet_ReturnsAddToCartUrl_OnCategoryPage_UnsecureUrl(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        /** @var HttpRequest $request */
        $request = Bootstrap::getObjectManager()->create(HttpRequest::class);
        //Emulate HTTPS request
        $request->getServer()->set('HTTPS', 'on');
        $request->getServer()->set('SERVER_PORT', 443);
        $request->setRouteName('catalog');
        $request->setControllerName('category');
        $request->setActionName('index');

        $url = $this->objectManager->create(Url::class, [
            'request' => $request,
        ]);

        $this->objectManager->addSharedInstance(
            instance: $url,
            className: Url::class,
        );

        /** @var AddToCartUrlProviderInterface $provider */
        $provider = $this->instantiateTestObject([
            'request' => $request,
        ]);
        $url = $provider->get(productId: 1);

        $this->assertStringContainsString(
            needle: 'http://sample.com/checkout/cart/add/uenc/',
            haystack: $url,
        );
    }

    /**
     * Note: isolation flushes the URL memory cache
     * @magentoAppIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_url https://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_link_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_link_url https://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     */
    public function testGet_ReturnsAddToCartUrl_OnCategoryPage_UnsecureServer(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        /** @var HttpRequest $request */
        $request = Bootstrap::getObjectManager()->create(HttpRequest::class);
        $request->setRouteName('catalog');
        $request->setControllerName('category');
        $request->setActionName('index');

        $url = $this->objectManager->create(Url::class, [
            'request' => $request,
        ]);

        $this->objectManager->addSharedInstance(
            instance: $url,
            className: Url::class,
        );

        /** @var AddToCartUrlProviderInterface $provider */
        $provider = $this->instantiateTestObject([
            'request' => $request,
        ]);
        $url = $provider->get(productId: 1);

        $this->assertStringContainsString(
            needle: 'http://sample.com/checkout/cart/add/uenc/',
            haystack: $url,
        );
    }

    /**
     * Note: isolation flushes the URL memory cache
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default_store web/unsecure/base_url http://sample.com/
     * @magentoConfigFixture default_store web/secure/base_url https://sample.com/
     * @magentoConfigFixture default_store web/unsecure/base_link_url http://sample.com/
     * @magentoConfigFixture default_store web/secure/base_link_url https://sample.com/
     * @magentoConfigFixture default_store web/secure/use_in_frontend 1
     * @magentoConfigFixture default_store web/seo/use_rewrites 1
     */
    public function testGet_ReturnsAddToCartUrl_OnCategoryPage_StoreNotSet(): void
    {
        /** @var HttpRequest $request */
        $request = Bootstrap::getObjectManager()->create(HttpRequest::class);
        //Emulate HTTPS request
        $request->getServer()->set('HTTPS', 'on');
        $request->getServer()->set('SERVER_PORT', 443);
        $request->setRouteName('catalog');
        $request->setControllerName('category');
        $request->setActionName('index');

        $url = $this->objectManager->create(Url::class, [
            'request' => $request,
        ]);

        $this->objectManager->addSharedInstance(
            instance: $url,
            className: Url::class,
        );

        /** @var AddToCartUrlProviderInterface $provider */
        $provider = $this->instantiateTestObject([
            'request' => $request,
        ]);
        $url = $provider->get();

        $this->assertStringContainsString(
            needle: 'https://sample.com/checkout/cart/add/uenc/',
            haystack: $url,
        );
        $this->assertStringNotContainsString(
            needle: '?___store=',
            haystack: $url,
        );
    }

    /**
     * Note: isolation flushes the URL memory cache
     * @magentoAppIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_url https://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_link_url http://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_link_url https://sample.com/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/use_in_frontend 1
     * @magentoConfigFixture klevu_test_store_1_store web/seo/use_rewrites 1
     */
    public function testGet_ReturnsAddToCartUrl_OnCartPage_ForProduct(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        /** @var HttpRequest $request */
        $request = Bootstrap::getObjectManager()->create(HttpRequest::class);
        //Emulate HTTPS request
        $request->getServer()->set('HTTPS', 'on');
        $request->getServer()->set('SERVER_PORT', 443);
        $request->setRouteName('checkout');
        $request->setControllerName('cart');
        $request->setActionName('index');

        $url = $this->objectManager->create(Url::class, [
            'request' => $request,
        ]);

        $this->objectManager->addSharedInstance(
            instance: $url,
            className: Url::class,
        );

        /** @var AddToCartUrlProviderInterface $provider */
        $provider = $this->instantiateTestObject([
            'request' => $request,
        ]);
        $url = $provider->get(productId: 1);

        $this->assertStringContainsString(
            needle: 'https://sample.com/checkout/cart/add/uenc/',
            haystack: $url,
        );
        $this->assertStringContainsString(
            needle: '/in_cart/1',
            haystack: $url,
        );
        $this->assertStringContainsString(
            needle: '/product/1',
            haystack: $url,
        );
        $this->assertStringContainsString(
            needle: '?___store=klevu_test_store_1',
            haystack: $url,
        );
    }
}
