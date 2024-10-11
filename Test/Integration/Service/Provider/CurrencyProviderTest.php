<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\Provider\CurrencyProvider;
use Klevu\FrontendApi\Service\Provider\Currency\RatesProviderInterface;
use Klevu\FrontendApi\Service\Provider\CurrencyProviderInterface;
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
 * @covers CurrencyProvider
 * @method CurrencyProviderInterface instantiateTestObject(?array $arguments = null)
 * @method CurrencyProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class CurrencyProviderTest extends TestCase
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

        $this->implementationFqcn = CurrencyProvider::class;
        $this->interfaceFqcn = CurrencyProviderInterface::class;
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

    public function testGet_ReturnsNull_WhenRatesProviderReturnsEmptyArray(): void
    {
        $mockRatesProvider = $this->getMockBuilder(RatesProviderInterface::class)
            ->getMock();
        $mockRatesProvider->expects($this->once())
            ->method('get')
            ->willReturn([]);

        $provider = $this->instantiateTestObject([
            'ratesProvider' => $mockRatesProvider,
        ]);
        $result = $provider->get();

        $this->assertCount(expectedCount: 0, haystack: $result);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsStringOfRates_ForGlobalScope(): void
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
            value: 'EUR,GBP,INR,JPY,KWD,USD',
        );

        $exchangeRates = [
            'USD' => [
                'EUR' => 0.8,
                'GBP' => 0.9,
                'INR' => 83.95,
                'JPY' => 150,
                'KWD' => 0.305,
            ],
        ];
        $this->setExchangeRates(rates: $exchangeRates);

        $provider = $this->instantiateTestObject();
        $result = $provider->get();

        $this->assertCount(expectedCount: 6, haystack: $result);

        $this->assertArrayHasKey(key: 'EUR', array: $result);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_RATE, array: $result['EUR']);
        $this->assertSame(expected: 0.8, actual: $result['EUR'][CurrencyProvider::CURRENCY_RATE]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_SYMBOL, array: $result['EUR']);
        $this->assertSame(expected: '€', actual: $result['EUR'][CurrencyProvider::CURRENCY_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_PRECISION, array: $result['EUR']);
        $this->assertSame(expected: 2, actual: $result['EUR'][CurrencyProvider::CURRENCY_PRECISION]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['EUR']);
        $this->assertSame(expected: '.', actual: $result['EUR'][CurrencyProvider::CURRENCY_DECIMAL_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['EUR']);
        $this->assertSame(expected: ',', actual: $result['EUR'][CurrencyProvider::CURRENCY_GROUP_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_LENGTH, array: $result['EUR']);
        $this->assertSame(expected: 3, actual: $result['EUR'][CurrencyProvider::CURRENCY_GROUP_LENGTH]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_APPEND_AT_LAST, array: $result['EUR']);
        $this->assertFalse(condition: $result['EUR'][CurrencyProvider::CURRENCY_APPEND_AT_LAST]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_FORMAT, array: $result['EUR']);
        $this->assertSame(expected: '%s%s', actual: $result['EUR'][CurrencyProvider::CURRENCY_FORMAT]);

        $this->assertArrayHasKey(key: 'GBP', array: $result);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_RATE, array: $result['GBP']);
        $this->assertSame(expected: 0.9, actual: $result['GBP'][CurrencyProvider::CURRENCY_RATE]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_SYMBOL, array: $result['GBP']);
        $this->assertSame(expected: '£', actual: $result['GBP'][CurrencyProvider::CURRENCY_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_PRECISION, array: $result['GBP']);
        $this->assertSame(expected: 2, actual: $result['GBP'][CurrencyProvider::CURRENCY_PRECISION]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['GBP']);
        $this->assertSame(expected: '.', actual: $result['GBP'][CurrencyProvider::CURRENCY_DECIMAL_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['GBP']);
        $this->assertSame(expected: ',', actual: $result['GBP'][CurrencyProvider::CURRENCY_GROUP_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_LENGTH, array: $result['GBP']);
        $this->assertSame(expected: 3, actual: $result['GBP'][CurrencyProvider::CURRENCY_GROUP_LENGTH]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_APPEND_AT_LAST, array: $result['GBP']);
        $this->assertFalse(condition: $result['GBP'][CurrencyProvider::CURRENCY_APPEND_AT_LAST]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_FORMAT, array: $result['GBP']);
        $this->assertSame(expected: '%s%s', actual: $result['GBP'][CurrencyProvider::CURRENCY_FORMAT]);

        $this->assertArrayHasKey(key: 'INR', array: $result);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_RATE, array: $result['INR']);
        $this->assertSame(expected: 83.95, actual: $result['INR'][CurrencyProvider::CURRENCY_RATE]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_SYMBOL, array: $result['INR']);
        $this->assertSame(expected: '₹', actual: $result['INR'][CurrencyProvider::CURRENCY_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_PRECISION, array: $result['INR']);
        $this->assertSame(expected: 2, actual: $result['INR'][CurrencyProvider::CURRENCY_PRECISION]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['INR']);
        $this->assertSame(expected: '.', actual: $result['INR'][CurrencyProvider::CURRENCY_DECIMAL_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['INR']);
        $this->assertSame(expected: ',', actual: $result['INR'][CurrencyProvider::CURRENCY_GROUP_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_LENGTH, array: $result['INR']);
        $this->assertSame(expected: 3, actual: $result['INR'][CurrencyProvider::CURRENCY_GROUP_LENGTH]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_APPEND_AT_LAST, array: $result['INR']);
        $this->assertFalse(condition: $result['INR'][CurrencyProvider::CURRENCY_APPEND_AT_LAST]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_FORMAT, array: $result['INR']);
        $this->assertSame(expected: '%s%s', actual: $result['INR'][CurrencyProvider::CURRENCY_FORMAT]);

        $this->assertArrayHasKey(key: 'JPY', array: $result);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_RATE, array: $result['JPY']);
        $this->assertSame(expected: 150.0, actual: $result['JPY'][CurrencyProvider::CURRENCY_RATE]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_SYMBOL, array: $result['JPY']);
        $this->assertSame(expected: '¥', actual: $result['JPY'][CurrencyProvider::CURRENCY_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_PRECISION, array: $result['JPY']);
        $this->assertSame(expected: 0, actual: $result['JPY'][CurrencyProvider::CURRENCY_PRECISION]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['JPY']);
        $this->assertSame(expected: '.', actual: $result['JPY'][CurrencyProvider::CURRENCY_DECIMAL_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['JPY']);
        $this->assertSame(expected: ',', actual: $result['JPY'][CurrencyProvider::CURRENCY_GROUP_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_LENGTH, array: $result['JPY']);
        $this->assertSame(expected: 3, actual: $result['JPY'][CurrencyProvider::CURRENCY_GROUP_LENGTH]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_APPEND_AT_LAST, array: $result['JPY']);
        $this->assertFalse(condition: $result['JPY'][CurrencyProvider::CURRENCY_APPEND_AT_LAST]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_FORMAT, array: $result['JPY']);
        $this->assertSame(expected: '%s%s', actual: $result['JPY'][CurrencyProvider::CURRENCY_FORMAT]);

        $this->assertArrayHasKey(key: 'KWD', array: $result);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_RATE, array: $result['KWD']);
        $this->assertSame(expected: 0.305, actual: $result['KWD'][CurrencyProvider::CURRENCY_RATE]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_SYMBOL, array: $result['KWD']);
        $this->assertSame(expected: 'KWD', actual: $result['KWD'][CurrencyProvider::CURRENCY_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_PRECISION, array: $result['KWD']);
        $this->assertSame(expected: 3, actual: $result['KWD'][CurrencyProvider::CURRENCY_PRECISION]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['KWD']);
        $this->assertSame(expected: '.', actual: $result['KWD'][CurrencyProvider::CURRENCY_DECIMAL_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['KWD']);
        $this->assertSame(expected: ',', actual: $result['KWD'][CurrencyProvider::CURRENCY_GROUP_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_LENGTH, array: $result['KWD']);
        $this->assertSame(expected: 3, actual: $result['KWD'][CurrencyProvider::CURRENCY_GROUP_LENGTH]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_APPEND_AT_LAST, array: $result['KWD']);
        $this->assertFalse(condition: $result['KWD'][CurrencyProvider::CURRENCY_APPEND_AT_LAST]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_FORMAT, array: $result['KWD']);
        $this->assertSame(expected: '%s %s', actual: $result['KWD'][CurrencyProvider::CURRENCY_FORMAT]);

        $this->assertArrayHasKey(key: 'USD', array: $result);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_RATE, array: $result['USD']);
        $this->assertSame(expected: 1.0, actual: $result['USD'][CurrencyProvider::CURRENCY_RATE]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_SYMBOL, array: $result['USD']);
        $this->assertSame(expected: '$', actual: $result['USD'][CurrencyProvider::CURRENCY_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_PRECISION, array: $result['USD']);
        $this->assertSame(expected: 2, actual: $result['USD'][CurrencyProvider::CURRENCY_PRECISION]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['USD']);
        $this->assertSame(expected: '.', actual: $result['USD'][CurrencyProvider::CURRENCY_DECIMAL_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['USD']);
        $this->assertSame(expected: ',', actual: $result['USD'][CurrencyProvider::CURRENCY_GROUP_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_LENGTH, array: $result['USD']);
        $this->assertSame(expected: 3, actual: $result['USD'][CurrencyProvider::CURRENCY_GROUP_LENGTH]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_APPEND_AT_LAST, array: $result['USD']);
        $this->assertFalse(condition: $result['USD'][CurrencyProvider::CURRENCY_APPEND_AT_LAST]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_FORMAT, array: $result['USD']);
        $this->assertSame(expected: '%s%s', actual: $result['USD'][CurrencyProvider::CURRENCY_FORMAT]);

    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsStringOfRates_ForStoreScope(): void
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
        ConfigFixture::setGlobal(
            path: DirctoryCurrency::XML_PATH_CURRENCY_ALLOW,
            value: 'USD',
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
        ];
        $this->setExchangeRates(rates: $exchangeRates);

        $provider = $this->instantiateTestObject();
        $result = $provider->get();

        $this->assertCount(expectedCount: 2, haystack: $result);

        $this->assertArrayNotHasKey(key: 'EUR', array: $result);

        $this->assertArrayHasKey(key: 'GBP', array: $result);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_RATE, array: $result['GBP']);
        $this->assertSame(expected: 0.9, actual: $result['GBP'][CurrencyProvider::CURRENCY_RATE]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_SYMBOL, array: $result['GBP']);
        $this->assertSame(expected: '£', actual: $result['GBP'][CurrencyProvider::CURRENCY_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_PRECISION, array: $result['GBP']);
        $this->assertSame(expected: 2, actual: $result['GBP'][CurrencyProvider::CURRENCY_PRECISION]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['GBP']);
        $this->assertSame(expected: '.', actual: $result['GBP'][CurrencyProvider::CURRENCY_DECIMAL_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['GBP']);
        $this->assertSame(expected: ',', actual: $result['GBP'][CurrencyProvider::CURRENCY_GROUP_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_LENGTH, array: $result['GBP']);
        $this->assertSame(expected: 3, actual: $result['GBP'][CurrencyProvider::CURRENCY_GROUP_LENGTH]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_APPEND_AT_LAST, array: $result['GBP']);
        $this->assertFalse(condition: $result['GBP'][CurrencyProvider::CURRENCY_APPEND_AT_LAST]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_FORMAT, array: $result['GBP']);
        $this->assertSame(expected: '%s%s', actual: $result['GBP'][CurrencyProvider::CURRENCY_FORMAT]);

        $this->assertArrayHasKey(key: 'USD', array: $result);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_RATE, array: $result['USD']);
        $this->assertSame(expected: 1.0, actual: $result['USD'][CurrencyProvider::CURRENCY_RATE]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_SYMBOL, array: $result['USD']);
        $this->assertSame(expected: '$', actual: $result['USD'][CurrencyProvider::CURRENCY_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_PRECISION, array: $result['USD']);
        $this->assertSame(expected: 2, actual: $result['USD'][CurrencyProvider::CURRENCY_PRECISION]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['USD']);
        $this->assertSame(expected: '.', actual: $result['USD'][CurrencyProvider::CURRENCY_DECIMAL_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_SYMBOL, array: $result['USD']);
        $this->assertSame(expected: ',', actual: $result['USD'][CurrencyProvider::CURRENCY_GROUP_SYMBOL]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_GROUP_LENGTH, array: $result['USD']);
        $this->assertSame(expected: 3, actual: $result['USD'][CurrencyProvider::CURRENCY_GROUP_LENGTH]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_APPEND_AT_LAST, array: $result['USD']);
        $this->assertFalse(condition: $result['USD'][CurrencyProvider::CURRENCY_APPEND_AT_LAST]);
        $this->assertArrayHasKey(key: CurrencyProvider::CURRENCY_FORMAT, array: $result['USD']);
        $this->assertSame(expected: '%s%s', actual: $result['USD'][CurrencyProvider::CURRENCY_FORMAT]);
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
