<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider;

use Klevu\Frontend\Service\Provider\CookieProvider;
use Klevu\FrontendApi\Service\Provider\CookieProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\Exception\InputException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Frontend\Service\Provider\CookieProvider
 * @magentoAppArea frontend
 */
class CookieProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = CookieProvider::class;
        $this->interfaceFqcn = CookieProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    public function testGet_ReturnsEmptyArray_WhenNoCookie(): void
    {
        $this->clearCookie();
        /** @var CookieProviderInterface $provider */
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: [],
            actual: $provider->get(name: 'klv_mage'),
        );
    }

    public function testGet_ReturnsEmptyArray_WhenCookieIsEmpty(): void
    {
        $this->clearCookie();
        $cookieManager = $this->objectManager->get(CookieManagerInterface::class);
        $cookieData = [];

        $serializer = $this->objectManager->get(Json::class);
        $cookieMetadata = $this->getCookieMetadata();

        $cookieManager->setPublicCookie(
            name: 'klv_mage',
            value: $serializer->serialize($cookieData),
            metadata: $cookieMetadata,
        );

        /** @var CookieProviderInterface $provider */
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: [],
            actual: $provider->get(name: 'klv_mage'),
        );
    }

    public function testGet_ReturnsCookieData_WhenCookieHasData(): void
    {
        $this->clearCookie();
        $cookieManager = $this->objectManager->get(CookieManagerInterface::class);
        $cookieData = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key_3' => [
                'thing1' => 123,
                'thing2' => true,
            ],
        ];
        $serializer = $this->objectManager->get(Json::class);
        $cookieMetadata = $this->getCookieMetadata();

        $cookieManager->setPublicCookie(
            name: 'klv_mage',
            value: $serializer->serialize($cookieData),
            metadata: $cookieMetadata,
        );

        /** @var CookieProviderInterface $provider */
        $provider = $this->instantiateTestObject();
        $this->assertSame(
            expected: $cookieData,
            actual: $provider->get(name: 'klv_mage'),
        );
    }

    /**
     * @param int|null $duration
     * @param string|null $path
     * @param bool|null $httpOnly
     *
     * @return PublicCookieMetadata
     */
    private function getCookieMetadata(
        ?int $duration = 84000,
        ?string $path = '/',
        ?bool $httpOnly = false,
    ): PublicCookieMetadata {
        $cookieMetadataFactory = $this->objectManager->get(CookieMetadataFactory::class);
        $cookieMetadata = $cookieMetadataFactory->createPublicCookieMetadata();
        $cookieMetadata->setDuration($duration);
        $cookieMetadata->setPath($path);
        $cookieMetadata->setHttpOnly($httpOnly);

        return $cookieMetadata;
    }

    /**
     * @return void
     * @throws InputException
     * @throws FailureToSendException
     */
    private function clearCookie(): void
    {
        $cookieManager = $this->objectManager->get(CookieManagerInterface::class);
        $cookieManager->deleteCookie('klv_mage');
    }
}
