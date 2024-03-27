<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Cookie;

use Klevu\Frontend\Service\Provider\Cookie\CookiePathProvider;
use Klevu\FrontendApi\Service\Provider\Cookie\CookiePathProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Service\Provider\Cookie\CookiePathProvider
 * @magentoAppArea frontend
 */
class CookiePathProviderTest extends TestCase
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

        $this->implementationFqcn = CookiePathProvider::class;
        $this->interfaceFqcn = CookiePathProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGet_ReturnsDefaultPath_WhenNotSet(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: '/',
            actual: $provider->get(),
        );
    }

    /**
     * @magentoConfigFixture default/web/cookie/cookie_path /global/path
     * @magentoConfigFixture default_store web/cookie/cookie_path /store/route
     */
    public function testGet_ReturnsStoreCookiePath(): void
    {
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: '/store/route',
            actual: $provider->get(),
        );
    }
}
