<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel;

use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Frontend\ViewModel\Cookie;
use Klevu\FrontendApi\ViewModel\CookieInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers Cookie
 * @method CookieInterface instantiateTestObject(?array $arguments = null)
 * @method CookieInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class CookieProviderViewModelTest extends TestCase
{
    use ObjectInstantiationTrait;
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

        $this->implementationFqcn = Cookie::class;
        $this->interfaceFqcn = CookieInterface::class;
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
     * @magentoConfigFixture default/web/cookie/cookie_lifetime 180
     * @magentoConfigFixture klevu_test_store_1_store web/cookie/cookie_lifetime 90
     */
    public function testGetCookieLifetime_ReturnsExpectedInt(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 90,
            actual: $viewModel->getCookieLifetime(),
        );
    }

    /**
     * @magentoConfigFixture default/web/cookie/cookie_path some-path
     * @magentoConfigFixture klevu_test_store_1_store web/cookie/cookie_path some-other-path
     */
    public function testGetCookiePath_ReturnsExpectedString(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'some-other-path',
            actual: $viewModel->getCookiePath(),
        );
    }
}
