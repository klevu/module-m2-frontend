<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Block\Adminhtml\Form\Field;

use Klevu\Frontend\Block\Adminhtml\Form\Field\TypeColumn;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\Element\BlockInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Block\Adminhtml\Form\Field\TypeColumn
 */
class TypeColumnTest extends TestCase
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

        $this->implementationFqcn = TypeColumn::class;
        $this->interfaceFqcn = BlockInterface::class;
        $this->expectPlugins = true;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testToHtml_IncludesExpectedOptions(): void
    {
        $block = $this->instantiateTestObject();
        $html = $block->_toHtml();

        $this->assertStringMatchesFormat(
            format: '<select%A>%A<option value="1"%w>Boolean</option>%A</select>',
            string: $html,
        );
        $this->assertStringMatchesFormat(
            format: '<select%A>%A<option value="2"%w>Integer</option>%A</select>',
            string: $html,
        );
        $this->assertStringMatchesFormat(
            format: '<select%A>%A<option value="3"%w>String</option>%A</select>',
            string: $html,
        );
    }
}
