<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\Frontend\Service\DelayPowerUpCondition\IsCustomerGroupPricingEnabledCondition;
use Klevu\Frontend\Service\Provider\CustomerGroupPricingEnabledProvider;
use Klevu\Frontend\Service\Provider\PowerUpProvider;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
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
 * @covers PowerUpProvider
 * @method SettingsProviderInterface instantiateTestObject(?array $arguments = null)
 * @method SettingsProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class PowerUpProviderTest extends TestCase
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

        $this->implementationFqcn = PowerUpProvider::class;
        $this->interfaceFqcn = SettingsProviderInterface::class;
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

    public function testGet_ThrowsOutputDisabledException_WhenStoreNotIntegrated(): void
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

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('Condition "klevu_integrated" is disabled');

        $provider = $this->instantiateTestObject([
            'delayPowerUpConditions' => [
                'is_group_pricing_enabled' => $this->objectManager->get(IsCustomerGroupPricingEnabledCondition::class),
            ],
        ]);
        $provider->get();
    }

    public function testGet_ThrowsOutputDisabledException_WhenPowerUpConditionDisabled(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-api-key',
            restAuthKey: 'klevu-rest-auth-key',
        );

        ConfigFixture::setForStore(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 0,
            storeCode: $storeFixture->getCode(),
        );

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('Condition "is_group_pricing_enabled" is not met');

        $provider = $this->instantiateTestObject([
            'delayPowerUpConditions' => [
                'is_group_pricing_enabled' => $this->objectManager->get(IsCustomerGroupPricingEnabledCondition::class),
            ],
        ]);
        $provider->get();
    }

    public function testGet_ReturnsFalse_WhenPowerUpConditionEnabled(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-api-key',
            restAuthKey: 'klevu-rest-auth-key',
        );

        ConfigFixture::setForStore(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 1,
            storeCode: $storeFixture->getCode(),
        );

        $provider = $this->instantiateTestObject([
            'delayPowerUpConditions' => [
                'is_group_pricing_enabled' => $this->objectManager->get(IsCustomerGroupPricingEnabledCondition::class),
            ],
        ]);
        $this->assertFalse(condition: $provider->get());
    }

    public function testGet_ThrowsOutputDisabledException_WhenNestedPowerUpConditionDisabled(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-api-key',
            restAuthKey: 'klevu-rest-auth-key',
        );

        ConfigFixture::setForStore(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 0,
            storeCode: $storeFixture->getCode(),
        );

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('All conditions are disabled');

        $provider = $this->instantiateTestObject([
            'delayPowerUpConditions' => [
                'delay_power_up_any_condition' => [
                    'is_group_pricing_enabled' => $this->objectManager->get(
                        IsCustomerGroupPricingEnabledCondition::class,
                    ),
                ],
            ],
        ]);
        $provider->get();
    }

    public function testGet_ReturnsFalse_WhenNestedPowerUpConditionEnabled(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-api-key',
            restAuthKey: 'klevu-rest-auth-key',
        );

        ConfigFixture::setForStore(
            path: CustomerGroupPricingEnabledProvider::XML_PATH_USE_CUSTOMER_GROUP_PRICING,
            value: 1,
            storeCode: $storeFixture->getCode(),
        );

        $provider = $this->instantiateTestObject([
            'delayPowerUpConditions' => [
                'delay_power_up_any_condition' => [
                    'is_group_pricing_enabled' => $this->objectManager->get(
                        IsCustomerGroupPricingEnabledCondition::class,
                    ),
                ],
            ],
        ]);
        $this->assertFalse(condition: $provider->get());
    }
}
