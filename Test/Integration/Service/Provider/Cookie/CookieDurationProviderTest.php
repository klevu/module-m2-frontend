<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Cookie;

use Klevu\Frontend\Service\Provider\Cookie\CookieDurationProvider;
use Klevu\FrontendApi\Service\Provider\Cookie\CookieDurationProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Service\Provider\Cookie\CookieDurationProvider
 * @magentoAppArea frontend
 */
class CookieDurationProviderTest extends TestCase
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

        $this->implementationFqcn = CookieDurationProvider::class;
        $this->interfaceFqcn = CookieDurationProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGet_ReturnsDefaultDuration_WhenNotSet(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: 3600,
            actual: $provider->get(),
        );
    }

    /**
     * @magentoConfigFixture default/web/cookie/cookie_lifetime 12345
     * @magentoConfigFixture default_store web/cookie/cookie_lifetime 98765
     */
    public function testGet_ReturnsStoreCookieDuration(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: 98765,
            actual: $provider->get(),
        );
    }
}
