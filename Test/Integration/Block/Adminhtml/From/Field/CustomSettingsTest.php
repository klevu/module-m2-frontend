<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Block\Adminhtml\Form\Field;

use Klevu\Frontend\Block\Adminhtml\Form\Field\CustomSettings;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Block\Adminhtml\Form\Field\CustomSettings
 */
class CustomSettingsTest extends TestCase
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

        $this->implementationFqcn = CustomSettings::class;
        $this->interfaceFqcn = RendererInterface::class;
        $this->expectPlugins = true;
        $this->objectManager = Bootstrap::getObjectManager();
    }
}
