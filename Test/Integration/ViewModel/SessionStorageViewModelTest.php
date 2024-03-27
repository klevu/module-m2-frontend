<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel;

use Klevu\Frontend\ViewModel\SessionStorage;
use Klevu\FrontendApi\ViewModel\SessionStorageInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers SessionStorage
 * @method SessionStorageInterface instantiateTestObject(?array $arguments = null)
 * @method SessionStorageInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class SessionStorageViewModelTest extends TestCase
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

        $this->implementationFqcn = SessionStorage::class;
        $this->interfaceFqcn = SessionStorageInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGetSessionStorageKey_ReturnsExpectedString(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'klv_mage',
            actual: $viewModel->getSessionStorageKey(),
        );
    }

    public function testGetCustomerDataSectionKey_ReturnsExpectedString(): void
    {
        $viewModel = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'customerData',
            actual: $viewModel->getSessionCustomerDataSectionKey(),
        );
    }
}
