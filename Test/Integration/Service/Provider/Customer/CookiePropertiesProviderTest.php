<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Customer;

use Klevu\Frontend\Service\Provider\Customer\CookiePropertiesProvider;
use Klevu\FrontendApi\Service\Provider\Customer\CookiePropertiesProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CookiePropertiesProviderTest extends TestCase
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

        $this->implementationFqcn = CookiePropertiesProvider::class;
        $this->interfaceFqcn = CookiePropertiesProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGetCookieKey_ReturnsExpectedString(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'klv_mage',
            actual: $provider->getCookieKey(),
        );
    }

    public function testGetExpireSectionsKey_ReturnsExpectedString(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: 'expire_sections',
            actual: $provider->getExpireSectionsKey(),
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
