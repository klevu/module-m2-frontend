<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Currency;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Frontend\Service\Provider\Currency\RatesProvider;
use Klevu\FrontendApi\Service\Provider\Currency\RatesProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Directory\Model\Currency as DirctoryCurrency;
use Magento\Directory\Model\ResourceModel\Currency as CurrencyResourceModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers RatesProvider
 * @method RatesProviderInterface instantiateTestObject(?array $arguments = null)
 * @method RatesProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class RatesProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = RatesProvider::class;
        $this->interfaceFqcn = RatesProviderInterface::class;
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

    public function testGet_ReturnsEmptyArray_WhenNoStoreSet(): void
    {
        $mockStoreScopeProvider = $this->getMockBuilder(StoreScopeProviderInterface::class)
            ->getMock();
        $mockStoreScopeProvider->expects($this->once())
            ->method('getCurrentStore')
            ->willReturn(null);

        $provider = $this->instantiateTestObject([
            'storeScopeProvider' => $mockStoreScopeProvider,
        ]);
        $result = $provider->get();

        $this->assertCount(expectedCount: 0, haystack: $result);
    }

    public function testGet_ReturnsExchangeRatesForStore_WhenSetAtGlobalScope(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        ConfigFixture::setGlobal(
            path: DirctoryCurrency::XML_PATH_CURRENCY_BASE,
            value: 'USD',
        );
        ConfigFixture::setGlobal(
            path: DirctoryCurrency::XML_PATH_CURRENCY_DEFAULT,
            value: 'USD',
        );
        ConfigFixture::setGlobal(
            path: DirctoryCurrency::XML_PATH_CURRENCY_ALLOW,
            value: 'GBP,EUR,USD',
        );

        $exchangeRates = [
            'USD' => [
                'EUR' => 0.8,
                'GBP' => 0.9,
            ],
            'EUR' => [
                'USD' => 1.2,
                'GBP' => 0.9,
            ],
            'GBP' => [
                'EUR' => 0.9,
                'USD' => 1.1,
            ],
        ];
        $this->setExchangeRates(rates: $exchangeRates);

        $provider = $this->instantiateTestObject();
        $result = $provider->get();

        $this->assertCount(expectedCount: 3, haystack: $result);
        $this->assertArrayHasKey(key: 'EUR', array: $result);
        $this->assertSame(expected: '0.800000000000', actual: $result['EUR']);
        $this->assertArrayHasKey(key: 'GBP', array: $result);
        $this->assertSame(expected: '0.900000000000', actual: $result['GBP']);
        $this->assertArrayHasKey(key: 'USD', array: $result);
        $this->assertSame(expected: '1.000000000000', actual: $result['USD']);
    }

    public function testGet_ReturnsExchangeRatesForStore_WhenSetAtStoreScope(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        ConfigFixture::setGlobal(
            path: DirctoryCurrency::XML_PATH_CURRENCY_BASE,
            value: 'USD',
        );
        ConfigFixture::setForStore(
            path: DirctoryCurrency::XML_PATH_CURRENCY_DEFAULT,
            value: 'USD',
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: DirctoryCurrency::XML_PATH_CURRENCY_ALLOW,
            value: 'GBP,USD',
            storeCode: $storeFixture->getCode(),
        );

        $exchangeRates = [
            'USD' => [
                'EUR' => 0.8,
                'GBP' => 0.9,
            ],
            'EUR' => [
                'USD' => 1.2,
                'GBP' => 0.9,
            ],
            'GBP' => [
                'EUR' => 0.9,
                'USD' => 1.1,
            ],
        ];
        $this->setExchangeRates(rates: $exchangeRates);

        $provider = $this->instantiateTestObject();
        $result = $provider->get();

        $this->assertCount(expectedCount: 2, haystack: $result);
        $this->assertArrayHasKey(key: 'GBP', array: $result);
        $this->assertSame(expected: '0.900000000000', actual: $result['GBP']);
        $this->assertArrayHasKey(key: 'USD', array: $result);
        $this->assertSame(expected: '1.000000000000', actual: $result['USD']);
    }

    /**
     * @param array<array<string, float>> $rates
     *
     * @return void
     * @throws LocalizedException
     */
    private function setExchangeRates(array $rates): void
    {
        $currencyResourceModel = $this->objectManager->get(CurrencyResourceModel::class);
        $currencyResourceModel->saveRates($rates);
    }
}
