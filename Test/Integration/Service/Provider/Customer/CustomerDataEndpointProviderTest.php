<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Customer;

use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Frontend\Service\Provider\Customer\CustomerDataEndpointProvider;
use Klevu\FrontendApi\Service\Provider\Customer\CustomerDataEndpointProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Service\Provider\Customer\CustomerDataEndpointProvider
 * @magentoAppArea frontend
 */
class CustomerDataEndpointProviderTest extends TestCase
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

        $this->implementationFqcn = CustomerDataEndpointProvider::class;
        $this->interfaceFqcn = CustomerDataEndpointProviderInterface::class;
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
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_url https://not_secure.test/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_url https://magento.test/
     */
    public function testGet_ReturnsCustomerDataEndpoint(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'https://magento.test/rest/V1/klevu/customerData',
            actual: $provider->get(),
        );
    }
}
