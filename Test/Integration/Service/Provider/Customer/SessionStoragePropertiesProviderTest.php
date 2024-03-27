<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Customer;

use Klevu\Frontend\Service\Provider\Customer\SessionStoragePropertiesProvider;
use Klevu\FrontendApi\Service\Provider\Customer\SessionStoragePropertiesProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SessionStoragePropertiesProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;

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

        $this->implementationFqcn = SessionStoragePropertiesProvider::class;
        $this->interfaceFqcn = SessionStoragePropertiesProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGetStorageKey_ReturnsExpectedString(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'klv_mage',
            actual: $provider->getStorageKey(),
        );
    }

    public function testGetCustomerDataSectionKey_ReturnsExpectedString(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'customerData',
            actual: $provider->getCustomerDataSectionKey(),
        );
    }
}
