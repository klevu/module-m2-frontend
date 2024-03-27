<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\ViewModel\CurrencyConversion;
use Klevu\FrontendApi\ViewModel\CurrencyConversionInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Directory\Model\Currency;
use Magento\Directory\Model\ResourceModel\Currency as CurrencyResourceModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers CurrencyConversion
 * @method CurrencyConversionInterface instantiateTestObject(?array $arguments = null)
 * @method CurrencyConversionInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class CurrencyConversionTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null; // @phpstan-ignore-line
    /**
     * @var StoreManagerInterface|null
     */
    private ?StoreManagerInterface $storeManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = CurrencyConversion::class;
        $this->interfaceFqcn = CurrencyConversionInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->storeManager = $this->objectManager->get(StoreManagerInterface::class);
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

    public function testIsEnabled_ReturnsFalse_WhenStoreNotIntegrated(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertFalse(condition: $viewModel->isEnabled());
    }

    public function testIsEnabled_ReturnsFalse_WhenStoreIntegrated_butNoModulesEnabled(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-123456789',
            restAuthKey: '1234567890ABCDEFGHI',
        );

        $viewModel = $this->instantiateTestObject();
        $this->assertFalse(condition: $viewModel->isEnabled());
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGetExchangeRate_ReturnsOne_WhenBaseCurrencyIsCurrencyCurrency(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        /** @var Store $store */
        $store = $storeFixture->get();

        ConfigFixture::setForStore(
            path: Currency::XML_PATH_CURRENCY_BASE,
            value: 'USD',
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: Currency::XML_PATH_CURRENCY_DEFAULT,
            value: 'USD',
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: Currency::XML_PATH_CURRENCY_ALLOW,
            value: 'USD,GBP,EUR',
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
        $store->setCurrentCurrencyCode('USD');
        $this->storeManager->setCurrentStore($store);

        $viewModel = $this->instantiateTestObject();
        $result = $viewModel->getExchangeRate();

        $this->assertSame(expected: 1.0, actual: $result);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation disabled
     */
    public function testGetExchangeRate_ReturnsRate_WhenNotBaseCurrency(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        /** @var Store $store */
        $store = $storeFixture->get();

        ConfigFixture::setGlobal(
            path: Currency::XML_PATH_CURRENCY_BASE,
            value: 'EUR',
        );
        ConfigFixture::setGlobal(
            path: Currency::XML_PATH_CURRENCY_DEFAULT,
            value: 'EUR',
        );
        ConfigFixture::setForStore(
            path: Currency::XML_PATH_CURRENCY_ALLOW,
            value: 'USD,GBP,EUR',
            storeCode: $storeFixture->getCode(),
        );

        $exchangeRates = [
            'USD' => [
                'EUR' => 0.8,
                'GBP' => 0.9,
            ],
            'EUR' => [
                'USD' => 1.2,
                'GBP' => 1.1,
            ],
            'GBP' => [
                'EUR' => 0.9,
                'USD' => 1.1,
            ],
        ];
        $this->setExchangeRates(rates: $exchangeRates);
        $store->setCurrentCurrencyCode('GBP');
        $this->storeManager->setCurrentStore($store);

        $viewModel = $this->instantiateTestObject();
        $this->assertSame(expected: 1.1, actual: $viewModel->getExchangeRate());
        $this->assertSame(expected: '£', actual: $viewModel->getCurrencySymbol());

        $store->setCurrentCurrencyCode('USD');
        $this->storeManager->setCurrentStore($store);

        $this->assertSame(expected: 1.2, actual: $viewModel->getExchangeRate());
        $this->assertSame(expected: '$', actual: $viewModel->getCurrencySymbol());

        $store->setCurrentCurrencyCode('EUR');
        $this->storeManager->setCurrentStore($store);

        $this->assertSame(expected: 1.0, actual: $viewModel->getExchangeRate());
        $this->assertSame(expected: '€', actual: $viewModel->getCurrencySymbol());
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
