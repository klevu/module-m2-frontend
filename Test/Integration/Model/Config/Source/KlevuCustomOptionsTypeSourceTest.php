<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Model\Config\Source;

use Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Phrase;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource
 */
class KlevuCustomOptionsTypeSourceTest extends TestCase
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

        $this->implementationFqcn = KlevuCustomOptionsTypeSource::class;
        $this->interfaceFqcn = OptionSourceInterface::class;
        $this->constructorArgumentDefaults = [
            'links' => [],
        ];
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testToOptionArray_ReturnsOptions(): void
    {
        $source = $this->instantiateTestObject();
        $options = $source->toOptionArray();

        $this->assertIsArray($options);
        $this->assertCount(expectedCount: 3, haystack: $options);
        $keys = array_keys($options);

        $expectedOptions = [
            0 => [
                'label' => 'Boolean',
                'value' => KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
            ],
            1 => [
                'label' => 'Integer',
                'value' => KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER,
            ],
            2 => [
                'label' => 'String',
                'value' => KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
            ],
        ];

        foreach ($expectedOptions as $key => $expectedOption) {
            $option = $options[$keys[$key]];
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertSame($expectedOption['value'], $option['value']);
            $this->assertInstanceOf(Phrase::class, $option['label']);
            $this->assertSame($expectedOption['label'], $option['label']->render());
        }
    }
}
