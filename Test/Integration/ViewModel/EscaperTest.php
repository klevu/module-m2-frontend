<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel;

use Klevu\Frontend\ViewModel\Escaper;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\Escaper as FrameworkEscaper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers Escaper
 * @method ArgumentInterface instantiateTestObject(?array $arguments = null)
 * @method ArgumentInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class EscaperTest extends TestCase
{
    use ObjectInstantiationTrait;
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

        $this->implementationFqcn = Escaper::class;
        $this->interfaceFqcn = ArgumentInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGetEscaper_ReturnsInstanceOf_FrameworkEscaper(): void
    {
        /** @var Escaper $escaper */
        $escaper = $this->instantiateTestObject();
        $this->assertInstanceOf(
            expected: FrameworkEscaper::class,
            actual: $escaper->getEscaper(),
        );
    }
}
