<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Unit\Service\Provider\Currency;

use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Frontend\Service\Provider\Currency\RatesProvider;
use Magento\Directory\Model\ResourceModel\Currency as CurrencyResourceModel;
use Magento\Store\Model\Store;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Service\Provider\Currency\RatesProvider
 */
class RatesProviderTest extends TestCase
{
    /**
     * @var CurrencyResourceModel|MockObject|null
     */
    private CurrencyResourceModel|MockObject|null $mockCurrencyResourceModel = null;
    /**
     * @var StoreScopeProviderInterface|MockObject|null
     */
    private StoreScopeProviderInterface|MockObject|null $mockStoreScopeProvider = null;

    protected function setUp(): void
    {
        $this->mockCurrencyResourceModel = $this->getMockBuilder(CurrencyResourceModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockStoreScopeProvider = $this->getMockBuilder(StoreScopeProviderInterface::class)
            ->getMock();
    }

    public function testGet_ReturnsSelfRateForBaseCurrency_WhenNoSelfRateRowExists(): void
    {
        $this->setStore(baseCurrencyCode: 'PLN', availableCurrencyCodes: ['PLN', 'EUR']);
        $this->mockCurrencyResourceModel->expects($this->once())
            ->method('getCurrencyRates')
            ->with('PLN', ['PLN', 'EUR'])
            ->willReturn(['EUR' => '0.230000000000']);

        $result = $this->instantiateRatesProvider()->get();

        $this->assertSame(
            expected: ['EUR' => '0.230000000000', 'PLN' => '1'],
            actual: $result,
        );
    }

    public function testGet_ReturnsStoredSelfRate_WhenSelfRateRowExists(): void
    {
        $this->setStore(baseCurrencyCode: 'USD', availableCurrencyCodes: ['USD', 'EUR']);
        $this->mockCurrencyResourceModel->method('getCurrencyRates')
            ->willReturn(['EUR' => '0.706700000000', 'USD' => '1.000000000000']);

        $result = $this->instantiateRatesProvider()->get();

        $this->assertSame(
            expected: ['EUR' => '0.706700000000', 'USD' => '1.000000000000'],
            actual: $result,
        );
    }

    public function testGet_DoesNotAddBaseCurrency_WhenNotAvailableForStore(): void
    {
        $this->setStore(baseCurrencyCode: 'PLN', availableCurrencyCodes: ['EUR']);
        $this->mockCurrencyResourceModel->method('getCurrencyRates')
            ->willReturn(['EUR' => '0.230000000000']);

        $result = $this->instantiateRatesProvider()->get();

        $this->assertSame(expected: ['EUR' => '0.230000000000'], actual: $result);
    }

    /**
     * @param string $baseCurrencyCode
     * @param string[] $availableCurrencyCodes
     *
     * @return void
     */
    private function setStore(string $baseCurrencyCode, array $availableCurrencyCodes): void
    {
        $mockStore = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBaseCurrencyCode', 'getAvailableCurrencyCodes'])
            ->getMock();
        $mockStore->method('getBaseCurrencyCode')
            ->willReturn($baseCurrencyCode);
        $mockStore->method('getAvailableCurrencyCodes')
            ->willReturn($availableCurrencyCodes);
        $this->mockStoreScopeProvider->method('getCurrentStore')
            ->willReturn($mockStore);
    }

    /**
     * @return RatesProvider
     */
    private function instantiateRatesProvider(): RatesProvider
    {
        return new RatesProvider(
            currencyResourceModel: $this->mockCurrencyResourceModel,
            storeScopeProvider: $this->mockStoreScopeProvider,
        );
    }
}
