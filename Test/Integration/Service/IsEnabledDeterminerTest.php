<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Exception\InvalidIsEnabledDeterminerException;
use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\Frontend\Service\IsEnabledCondition\IsStoreIntegratedCondition;
use Klevu\Frontend\Service\IsEnabledDeterminer;
use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;
use Klevu\FrontendApi\Service\IsEnabledDeterminerInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\App\State as AppState;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Klevu\Frontend\Service\IsEnabledDeterminer
 * @magentoAppArea frontend
 */
class IsEnabledDeterminerTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $mockLogger;
    /**
     * @var AppState|MockObject
     */
    private AppState|MockObject $mockAppState;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = IsEnabledDeterminer::class;
        $this->interfaceFqcn = IsEnabledDeterminerInterface::class;
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

        /** @var IsEnabledDeterminer $service */
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
            'IsEnabledCondition "%s" must be instance of %s; %s received',
            'key',
            IsEnabledConditionInterface::class,
            get_debug_type($invalidCondition),
        );
        $this->mockLogger->expects($this->once())
            ->method('warning')
            ->with(
                'Method: {method}, Warning: {warning}',
                [
                    'method' => 'Klevu\Frontend\Service\IsEnabledDeterminer::handleInvalidCondition',
                    'warning' => $message,
                ],
            );
        $this->mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_PRODUCTION);

        /** @var IsEnabledDeterminer $service */
        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $service->executeAnd([
            'key' => $invalidCondition,
        ]);
    }

    /**
     * @dataProvider dataProvider_InvalidConditions
     */
    public function testExecuteAnd_ThrowsException_WhenInvalidCondition_DeveloperMode(mixed $invalidCondition): void
    {
        $message = sprintf(
            'IsEnabledCondition "%s" must be instance of %s; %s received',
            'key',
            IsEnabledConditionInterface::class,
            get_debug_type($invalidCondition),
        );
        $this->expectException(InvalidIsEnabledDeterminerException::class);
        $this->expectExceptionMessage($message);

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_DEVELOPER);

        /** @var IsEnabledDeterminer $service */
        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $service->executeAnd([
            'key' => $invalidCondition,
        ]);
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

    /**
     * @dataProvider dataProvider_testExecuteAnd_ThrowsException_WhenConditionIsNotMet
     */
    public function testExecuteAnd_ThrowsException_WhenConditionIsNotMet(
        ?string $jsApiKey = null,
        ?string $restAuthKey = null,
    ): void {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('Condition "key" is disabled');

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: $jsApiKey,
            restAuthKey: $restAuthKey,
        );

        /** @var IsEnabledDeterminer $service */
        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $isIntegratedCondition = $this->objectManager->get(IsStoreIntegratedCondition::class);
        $service->executeAnd([
            'key' => $isIntegratedCondition,
        ]);
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testExecuteAnd_ThrowsException_WhenConditionIsNotMet(): array
    {
        return [
            [null, null],
            [null, 'klevu-rest-key'],
            ['klevu-js-key', null],
        ];
    }

    public function testExecuteAnd_WhenConditionIsMet(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        /** @var IsEnabledDeterminer $service */
        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $isIntegratedCondition = $this->objectManager->get(IsStoreIntegratedCondition::class);
        $service->executeAnd([
            'key' => $isIntegratedCondition,
        ]);
    }

    public function testExecuteAnd_ThrowsException_WhenOrConditionIsNotMet(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('All conditions are disabled');

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-api-key',
            restAuthKey: null,
        );
        /** @var IsEnabledDeterminer $service */
        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $isIntegratedCondition = $this->objectManager->get(IsStoreIntegratedCondition::class);
        $service->executeAnd(
            isEnabledConditions: [
                'or_condition_test' => [
                    'integrated' => $isIntegratedCondition,
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

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-api-key',
            restAuthKey: 'klevu-rest-auth-key',
        );
        /** @var IsEnabledDeterminer $service */
        $service = $this->instantiateTestObject([
            'appState' => $this->mockAppState,
            'logger' => $this->mockLogger,
        ]);
        $isIntegratedCondition = $this->objectManager->get(IsStoreIntegratedCondition::class);
        $service->executeAnd(
            isEnabledConditions: [
                'or_condition_test' => [
                    'integrated' => $isIntegratedCondition,
                ],
            ],
        );
    }
}
