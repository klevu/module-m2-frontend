<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\DelayPowerUpCondition;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\DelayPowerUpCondition\IsCustomerGroupPricingEnabledCondition;
use Klevu\Frontend\Service\Provider\CustomerGroupPricingEnabledProvider;
use Klevu\FrontendApi\Service\DelayPowerUpCondition\DelayPowerUpConditionInterface;
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
 * @covers IsCustomerGroupPricingEnabledCondition
 * @method DelayPowerUpConditionInterface instantiateTestObject(?array $arguments = null)
 * @method DelayPowerUpConditionInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class IsCustomerGroupPricingEnabledConditionTest extends TestCase
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

        $this->implementationFqcn = IsCustomerGroupPricingEnabledCondition::class;
        $this->interfaceFqcn = DelayPowerUpConditionInterface::class;
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

    public function testGet_ReturnsDefaultValue(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertFalse(condition: $provider->execute());
    }

    public function testGet_ReturnsFalse_WhenDisabled_Globally(): void
    {
        ConfigFixture::setGlobal(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 0,
        );

        $provider = $this->instantiateTestObject();
        $this->assertFalse(condition: $provider->execute());
    }

    public function testGet_ReturnsTrue_WhenEnabled_Globally(): void
    {
        ConfigFixture::setGlobal(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 1,
        );

        $provider = $this->instantiateTestObject();
        $this->assertTrue(condition: $provider->execute());
    }

    public function testGet_ReturnsFalse_WhenDisabled_AtStoreScope(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        ConfigFixture::setForStore(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 0,
            storeCode: $store->getCode(),
        );

        $provider = $this->instantiateTestObject();
        $this->assertFalse(condition: $provider->execute());
    }

    public function testGet_ReturnsTrue_WhenEnabled_AtStoreScope(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        ConfigFixture::setForStore(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 1,
            storeCode: $store->getCode(),
        );

        $provider = $this->instantiateTestObject();
        $this->assertTrue(condition: $provider->execute());
    }
}
