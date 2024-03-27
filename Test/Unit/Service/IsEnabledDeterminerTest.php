<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Unit\Service;

use Klevu\Frontend\Exception\OutputDisabledException;
use Klevu\Frontend\Service\IsEnabledDeterminer;
use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;
use Magento\Framework\App\State as AppState;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Klevu\Frontend\Service\IsEnabledDeterminer
 */
class IsEnabledDeterminerTest extends TestCase
{
    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface&MockObject $mockLogger;
    /**
     * @var AppState&MockObject
     */
    private AppState&MockObject $mockAppState;

    protected function setUp(): void
    {
        $this->mockLogger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testExecute_HandlesAndLogic_Enabled(): void
    {
        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $mockCondition1 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition1->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $mockCondition2 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition2->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $mockCondition3 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition3->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $service = new IsEnabledDeterminer($this->mockLogger, $this->mockAppState);
        $service->executeAnd(
            isEnabledConditions: [
                'condition_1' => $mockCondition1,
                'condition_2' => $mockCondition2,
                'condition_3' => $mockCondition3,
            ],
        );
    }

    /**
     * @dataProvider dataProvider_testExecute_HandlesAndLogic_Disabled
     */
    public function testExecute_HandlesAndLogic_Disabled(bool $condition1, bool $condition2, bool $condition3): void
    {
        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessageMatches('#Condition ".*" is disabled#');

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $mockCondition1 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition1->method('execute')
            ->willReturn($condition1);

        $mockCondition2 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition2->method('execute')
            ->willReturn($condition2);

        $mockCondition3 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition3->method('execute')
            ->willReturn($condition3);

        $service = new IsEnabledDeterminer($this->mockLogger, $this->mockAppState);
        $service->executeAnd(
            isEnabledConditions: [
                'condition_1' => $mockCondition1,
                'condition_2' => $mockCondition2,
                'condition_3' => $mockCondition3,
            ],
        );
    }

    /**
     * @return bool[][]
     */
    public function dataProvider_testExecute_HandlesAndLogic_Disabled(): array
    {
        return [
            [true, true, false],
            [true, false, true],
            [false, true, true],
            [false, false, true],
            [true, true, false],
            [false, false, false],
        ];
    }

    /**
     * @dataProvider dataProvider_testExecute_HandlesOrLogic_Enabled
     */
    public function testExecute_HandlesOrLogic_Enabled(bool $condition1, bool $condition2, bool $condition3): void
    {
        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $mockCondition1 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition1->method('execute')
            ->willReturn($condition1);

        $mockCondition2 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition2->method('execute')
            ->willReturn($condition2);

        $mockCondition3 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition3->method('execute')
            ->willReturn($condition3);

        $service = new IsEnabledDeterminer($this->mockLogger, $this->mockAppState);
        $service->executeAnd(
            isEnabledConditions: [
                'or_logic' => [
                    'condition_1' => $mockCondition1,
                    'condition_2' => $mockCondition2,
                    'condition_3' => $mockCondition3,
                ],
            ],
        );
    }

    /**
     * @return bool[][]
     */
    public function dataProvider_testExecute_HandlesOrLogic_Enabled(): array
    {
        return [
            [true, true, false],
            [true, false, true],
            [false, true, true],
            [false, false, true],
            [true, true, false],
            [true, true, true],
        ];
    }

    public function testExecute_HandlesOrLogic_Disabled(): void
    {
        $this->expectException(OutputDisabledException::class);
        $this->expectExceptionMessage('All conditions are disabled');

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $mockCondition1 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition1->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $mockCondition2 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition2->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $mockCondition3 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition3->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $service = new IsEnabledDeterminer($this->mockLogger, $this->mockAppState);
        $service->executeAnd(
            isEnabledConditions: [
                'or_logic' => [
                    'condition_1' => $mockCondition1,
                    'condition_2' => $mockCondition2,
                    'condition_3' => $mockCondition3,
                ],
            ],
        );
    }

    /**
     * @dataProvider dataProvider_testExecute_HandlesBothLogics_Enabled
     */
    public function testExecute_HandlesBothLogics_Enabled(bool $condition1, bool $condition2, bool $condition3): void
    {
        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $mockCondition1 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition1->method('execute')
            ->willReturn($condition1);

        $mockCondition2 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition2->method('execute')
            ->willReturn($condition2);

        $mockCondition3 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition3->method('execute')
            ->willReturn($condition3);

        $service = new IsEnabledDeterminer($this->mockLogger, $this->mockAppState);
        $service->executeAnd(
            isEnabledConditions: [
                'condition_1' => $mockCondition1,
                'or_logic' => [
                    'condition_2' => $mockCondition2,
                    'condition_3' => $mockCondition3,
                ],
            ],
        );
    }

    /**
     * @return bool[][]
     */
    public function dataProvider_testExecute_HandlesBothLogics_Enabled(): array
    {
        return [
            [true, true, false],
            [true, false, true],
            [true, true, false],
            [true, true, true],
        ];
    }

    /**
     * @dataProvider dataProvider_testExecute_HandlesBothLogics_Disabled
     */
    public function testExecute_HandlesBothLogics_Disabled(bool $condition1, bool $condition2, bool $condition3): void
    {
        $this->expectException(OutputDisabledException::class);

        $this->mockLogger->expects($this->never())
            ->method('warning');
        $this->mockAppState->expects($this->never())
            ->method('getMode');

        $mockCondition1 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition1->method('execute')
            ->willReturn($condition1);

        $mockCondition2 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition2->method('execute')
            ->willReturn($condition2);

        $mockCondition3 = $this->getMockBuilder(IsEnabledConditionInterface::class)
            ->disableOriginalConstructor()->getMock();
        $mockCondition3->method('execute')
            ->willReturn($condition3);

        $service = new IsEnabledDeterminer($this->mockLogger, $this->mockAppState);
        $service->executeAnd(
            isEnabledConditions: [
                'condition_1' => $mockCondition1,
                'or_logic' => [
                    'condition_2' => $mockCondition2,
                    'condition_3' => $mockCondition3,
                ],
            ],
        );
    }

    /**
     * @return bool[][]
     */
    public function dataProvider_testExecute_HandlesBothLogics_Disabled(): array
    {
        return [
            [true, false, false],
            [false, true, true],
            [false, true, false],
            [false, false, true],
        ];
    }
}
