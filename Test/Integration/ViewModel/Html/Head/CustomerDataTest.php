<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel\Html\Head;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\IsEnabledCondition\IsStoreIntegratedCondition;
use Klevu\Frontend\ViewModel\Html\Head\CustomerData;
use Klevu\FrontendApi\ViewModel\Html\Head\CustomerDataInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\ViewModel\Html\Head\CustomerData::class
 * @magentoAppArea frontend
 */
class CustomerDataTest extends TestCase
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

        $this->implementationFqcn = CustomerData::class;
        $this->interfaceFqcn = CustomerDataInterface::class;
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

    public function testIsOutputEnabled_ReturnsFalse_WhenNotIntegrated(): void
    {
        /** @var CustomerDataInterface $viewModel */
        $viewModel = $this->instantiateTestObject([
            'isEnabledConditions' => [
                'klevu_integrated' => $this->objectManager->get(IsStoreIntegratedCondition::class),
            ],
        ]);

        $this->assertFalse($viewModel->isOutputEnabled());
    }

    public function testIsOutputEnabled_ReturnsTrue_WhenIntegrated(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );

        /** @var CustomerDataInterface $viewModel */
        $viewModel = $this->instantiateTestObject([
            'isEnabledConditions' => [
                'klevu_integrated' => $this->objectManager->get(IsStoreIntegratedCondition::class),
            ],
        ]);

        $this->assertTrue($viewModel->isOutputEnabled());
    }

    public function testGetCustomerDataKey_ReturnsExpectedString(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'customerData',
            actual: $viewModel->getCustomerDataKey(),
        );
    }

    public function testGetCookieKey_ReturnsExpectedString(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'klv_mage',
            actual: $viewModel->getCookieKey(),
        );
    }

    public function testGetExpireSectionsKey_ReturnsExpectedString(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'expire_sections',
            actual: $viewModel->getExpireSectionsKey(),
        );
    }

    public function testGetCustomerDataSectionLifetime_ReturnsExpectedInt(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 600,
            actual: $viewModel->getCustomerDataSectionLifetime(),
        );
    }

    public function testGetCustomerDataLoadedEventName_ReturnsExpectedString(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'klevu.customerData.loaded',
            actual: $viewModel->getCustomerDataLoadedEventName(),
        );
    }

    public function testGetCustomerDataLoadErrorEventName_ReturnsExpectedString(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'klevu.customerData.loadError',
            actual: $viewModel->getCustomerDataLoadErrorEventName(),
        );
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store web/unsecure/base_url https://not_secure.test/
     * @magentoConfigFixture klevu_test_store_1_store web/secure/base_url https://magento.test/
     */
    public function testGetCustomerDataApiEndpoint_ReturnsExpectedString(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $viewModel = $this->instantiateTestObject();
        $endpoint = $viewModel->getCustomerDataApiEndpoint();
        $this->assertSame(
            expected: 'https://magento.test/rest/V1/klevu/customerData',
            actual: $endpoint,
        );
    }
}
