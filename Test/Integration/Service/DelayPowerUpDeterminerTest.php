<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Exception\InvalidDelayPowerUpDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\Frontend\Service\DelayPowerUpCondition\IsCustomerGroupPricingEnabledCondition;
use Klevu\Frontend\Service\DelayPowerUpDeterminer;
use Klevu\Frontend\Service\Provider\CustomerGroupPricingEnabledProvider;
use Klevu\FrontendApi\Service\DelayPowerUpCondition\DelayPowerUpConditionInterface;
use Klevu\FrontendApi\Service\DelayPowerUpDeterminerInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\App\State as AppState;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers DelayPowerUpDeterminer
 * @method DelayPowerUpDeterminerInterface instantiateTestObject(?array $arguments = null)
 * @method DelayPowerUpDeterminerInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class DelayPowerUpDeterminerTest extends TestCase
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
     * @var LoggerInterface|MockObject|null
     */
    private LoggerInterface | MockObject | null $mockLogger = null;
    /**
     * @var AppState|MockObject|null
     */
    private AppState | MockObject | null $mockAppState = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = DelayPowerUpDeterminer::class;
        $this->interfaceFqcn = DelayPowerUpDeterminerInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
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

    public function testExecuteAnd_ReturnsVoid_WhenNoConditionsPresent(): void
    {
        $this->mockLogger->expects($this->never())->method('error');
        $this->mockAppState->expects($this->never())->method('getMode');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $service->executeAnd([]);
    }

    /**
     * @dataProvider dataProvider_InvalidConditions
     */
    public function testExecuteAnd_LogsError_WhenInvalidCondition_ProductionMode(mixed $invalidCondition): void
    {
        $message = sprintf(
            'delayPowerUpCondition "%s" must be instance of %s; %s received',
            'key',
            DelayPowerUpConditionInterface::class,
            get_debug_type($invalidCondition),
        );
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with(
                'Method: {method}, Warning: {warning}',
                [
                    'method' => 'Klevu\Frontend\Service\DelayPowerUpDeterminer::handleInvalidCondition',
                    'warning' => $message,
                ],
            );
        $this->mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_PRODUCTION);

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $service->executeAnd(
            delayPowerUpConditions: [
                'key' => $invalidCondition,
            ],
        );
    }

    /**
     * @dataProvider dataProvider_InvalidConditions
     */
    public function testExecuteAnd_ThrowsException_WhenInvalidCondition_DeveloperMode(mixed $invalidCondition): void
    {
        $message = sprintf(
            'delayPowerUpCondition "%s" must be instance of %s; %s received',
            'key',
            DelayPowerUpConditionInterface::class,
            get_debug_type($invalidCondition),
        );
        $this->expectException(InvalidDelayPowerUpDeterminerException::class);
        $this->expectExceptionMessage($message);

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_DEVELOPER);

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $service->executeAnd(
            delayPowerUpConditions: [
                'key' => $invalidCondition,
            ],
        );
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_InvalidConditions(): array
    {
        return [
            [null],
            [false],
            [true],
            [0],
            [1],
            [12.34],
            ['string'],
            [new DataObject()],
        ];
    }

    public function testExecuteAnd_ThrowsException_WhenConditionIsNotMet(): void
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

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('Condition "is_group_pricing_enabled" is not met');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $isGroupPricingEnabledCondition = $this->objectManager->get(IsCustomerGroupPricingEnabledCondition::class);
        $service->executeAnd(
            delayPowerUpConditions: [
                'is_group_pricing_enabled' => $isGroupPricingEnabledCondition,
            ],
        );
    }

    public function testExecuteAnd_WhenConditionIsMet(): void
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

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $isGroupPriceEnabled = $this->objectManager->get(IsCustomerGroupPricingEnabledCondition::class);
        $service->executeAnd(
            delayPowerUpConditions: [
                'is_group_pricing_enabled' => $isGroupPriceEnabled,
            ],
        );
    }

    public function testExecuteAnd_ThrowsException_WhenOrConditionIsNotMet(): void
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

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('All conditions are disabled');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $isGroupPriceEnabled = $this->objectManager->get(IsCustomerGroupPricingEnabledCondition::class);
        $service->executeAnd(
            delayPowerUpConditions: [
                'or_condition_test' => [
                    'is_group_pricing_enabled' => $isGroupPriceEnabled,
                ],
            ],
        );
    }

    public function testExecuteAnd_WhenOrConditionIsMet(): void
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

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $isGroupPriceEnabled = $this->objectManager->get(IsCustomerGroupPricingEnabledCondition::class);
        $mockDelayPowerUpCondition = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition->method('execute')
            ->willReturn(false);

        $service->executeAnd(
            delayPowerUpConditions: [
                'or_condition_test' => [
                    'is_group_pricing_enabled' => $isGroupPriceEnabled,
                    'other_delay_condition' => $mockDelayPowerUpCondition,
                ],
            ],
        );
    }

    public function testExecuteAnd_WhenAndConditionIsMet_MultiValues(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $mockDelayPowerUpCondition1 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition1->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $mockDelayPowerUpCondition2 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition2->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $service->executeAnd(
            delayPowerUpConditions: [
                'this' => $mockDelayPowerUpCondition1,
                'and_this' => $mockDelayPowerUpCondition2,
            ],
        );
    }

    public function testExecuteAnd_WhenAndConditionIsNotMet_MultiValues(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('Condition "and_this" is not met');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $mockDelayPowerUpCondition1 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition1->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $mockDelayPowerUpCondition2 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition2->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $service->executeAnd(
            delayPowerUpConditions: [
                'this' => $mockDelayPowerUpCondition1,
                'and_this' => $mockDelayPowerUpCondition2,
            ],
        );
    }

    public function testExecuteAnd_WhenOrConditionIsMet_MultiValues(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $mockDelayPowerUpCondition1 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition1->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $mockDelayPowerUpCondition2 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition2->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $mockDelayPowerUpCondition3 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition3->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $service->executeAnd(
            delayPowerUpConditions: [
                'this' => $mockDelayPowerUpCondition1,
                'and_this' => [
                    'that' => $mockDelayPowerUpCondition2,
                    'or_that' => $mockDelayPowerUpCondition3,
                ],
            ],
        );
    }

    public function testExecuteAnd_WhenOrConditionIsNotMet_MultiValues(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('All conditions are disabled');

        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $mockDelayPowerUpCondition1 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition1->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $mockDelayPowerUpCondition2 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition2->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $mockDelayPowerUpCondition3 = $this->getMockBuilder(DelayPowerUpConditionInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockDelayPowerUpCondition3->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $service->executeAnd(
            delayPowerUpConditions: [
                'this' => $mockDelayPowerUpCondition1,
                'and_this' => [
                    'that' => $mockDelayPowerUpCondition2,
                    'or_that' => $mockDelayPowerUpCondition3,
                ],
            ],
        );
    }
}
