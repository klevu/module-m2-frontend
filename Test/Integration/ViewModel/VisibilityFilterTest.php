<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel;

use Klevu\Frontend\ViewModel\VisibilityFilter;
use Klevu\FrontendApi\ViewModel\VisibilityFilterInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers VisibilityFilter
 * @method VisibilityFilterInterface instantiateTestObject(?array $arguments = null)
 * @method VisibilityFilterInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class VisibilityFilterTest extends TestCase
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

        $this->implementationFqcn = VisibilityFilter::class;
        $this->interfaceFqcn = VisibilityFilterInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testIsEnabled_ReturnsFalse_WhenStoreNotIntegrated(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertFalse(condition: $viewModel->isEnabled());
    }
}
