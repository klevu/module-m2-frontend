<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\IsEnabledCondition;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\IsEnabledCondition\IsCustomerGroupPriceEnabledCondition;
use Klevu\Frontend\Service\Provider\CustomerGroupPricingEnabledProvider;
use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers IsStoreIntegratedCondition
 * @method IsEnabledConditionInterface instantiateTestObject(?array $arguments = null)
 * @method IsEnabledConditionInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class IsCustomerGroupPriceEnabledConditionTest extends TestCase
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

        $this->implementationFqcn = IsCustomerGroupPriceEnabledCondition::class;
        $this->interfaceFqcn = IsEnabledConditionInterface::class;
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

    public function testExecute_ReturnsFalse_AtGlobalScope(): void
    {
        ConfigFixture::setGlobal(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 0,
        );

        $service = $this->instantiateTestObject();
        $this->assertFalse($service->execute());
    }
    
    public function testExecute_ReturnsTrue_AtGlobalScope(): void
    {
        ConfigFixture::setGlobal(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 1,
        );

        $service = $this->instantiateTestObject();
        $this->assertTrue($service->execute());
    }

    public function testExecute_ReturnsFalse_AtStoreScope(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        ConfigFixture::setForStore(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 0,
            storeCode: $storeFixture->getCode(),
        );

        $service = $this->instantiateTestObject();
        $this->assertFalse($service->execute());
    }

    public function testExecute_ReturnsTrue_AtStoreScope(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        ConfigFixture::setForStore(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 1,
            storeCode: $storeFixture->getCode(),
        );

        $service = $this->instantiateTestObject();
        $this->assertTrue($service->execute());
    }
}
