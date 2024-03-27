<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Layout;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\Provider\Layout\RelLinkProvider;
use Klevu\FrontendApi\Service\Provider\Layout\RelLinkProviderInterface;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Frontend\Service\Provider\Layout\RelLinkProvider
 * @magentoAppArea frontend
 */
class RelLinkProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
    use StoreTrait;
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

        $this->implementationFqcn = RelLinkProvider::class;
        $this->interfaceFqcn = RelLinkProviderInterface::class;
        $this->constructorArgumentDefaults = [
            'links' => [],
        ];
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->storeFixturesPool->rollback();
    }

    public function testGet_FiltersOutLinks_WithMissingData(): void
    {
        $provider = $this->instantiateTestObject(
            arguments: [
                'links' => [
                    [RelLinkProviderInterface::RESOURCE_PATH => 'klevu/test/path'],
                    [RelLinkProviderInterface::RESOURCE_TYPE => 'type'],
                ],
            ],
        );
        $relLinks = $provider->get();

        $this->assertCount(expectedCount: 0, haystack: $relLinks);
    }

    public function testGet_FiltersOutLinks_WhenPathIsInvalidType(): void
    {
        $provider = $this->instantiateTestObject(
            arguments: [
                'links' => [
                    [
                        RelLinkProviderInterface::RESOURCE_PATH => 12,
                        RelLinkProviderInterface::RESOURCE_TYPE => 'type',
                    ],
                ],
            ],
        );
        $relLinks = $provider->get();

        $this->assertCount(expectedCount: 0, haystack: $relLinks);
    }

    public function testGet_FiltersOutLinks_WhenPathIsIncorrectObject(): void
    {
        $mockObjectBuilder = $this->getMockBuilder(DataObject::class);
        $mockObjectBuilder->addMethods(['get']);
        $mockObject = $mockObjectBuilder->disableOriginalConstructor()
            ->getMock();
        $mockObject->method('get')->willReturn('path');

        $provider = $this->instantiateTestObject(
            arguments: [
                'links' => [
                    [
                        RelLinkProviderInterface::RESOURCE_PATH => $mockObject,
                        RelLinkProviderInterface::RESOURCE_TYPE => 'type',
                    ],
                ],
            ],
        );
        $relLinks = $provider->get();

        $this->assertCount(expectedCount: 0, haystack: $relLinks);
    }

    public function testGet_ReturnsData_WhenPathTypeIsString(): void
    {
        $provider = $this->instantiateTestObject(
            arguments: [
                'links' => [
                    [
                        RelLinkProviderInterface::RESOURCE_PATH => 'the-path',
                        RelLinkProviderInterface::RESOURCE_TYPE => 'type',
                    ],
                ],
            ],
        );
        $relLinks = $provider->get();

        $this->assertCount(expectedCount: 1, haystack: $relLinks);
        $link = array_shift($relLinks);
        $this->assertArrayHasKey(key: RelLinkProviderInterface::RESOURCE_PATH, array: $link);
        $this->assertSame(expected: 'the-path', actual: $link[RelLinkProviderInterface::RESOURCE_PATH]);
        $this->assertArrayHasKey(key: RelLinkProviderInterface::RESOURCE_TYPE, array: $link);
        $this->assertSame(expected: 'type', actual: $link[RelLinkProviderInterface::RESOURCE_TYPE]);
    }

    public function testGet_ReturnsData_WhenPathTypeIsInstanceOf_SettingsProviderInterface(): void
    {
        $mockObject = $this->getMockBuilder(SettingsProviderInterface::class)
            ->getMock();
        $mockObject->method('get')->willReturn('the-path');

        $provider = $this->instantiateTestObject(
            arguments: [
                'links' => [
                    [
                        RelLinkProviderInterface::RESOURCE_PATH => $mockObject,
                        RelLinkProviderInterface::RESOURCE_TYPE => 'type',
                    ],
                ],
            ],
        );
        $relLinks = $provider->get();

        $this->assertCount(expectedCount: 1, haystack: $relLinks);
        $link = array_shift($relLinks);
        $this->assertArrayHasKey(key: RelLinkProviderInterface::RESOURCE_PATH, array: $link);
        $this->assertSame(expected: 'the-path', actual: $link[RelLinkProviderInterface::RESOURCE_PATH]);
        $this->assertArrayHasKey(key: RelLinkProviderInterface::RESOURCE_TYPE, array: $link);
        $this->assertSame(expected: 'type', actual: $link[RelLinkProviderInterface::RESOURCE_TYPE]);
    }

    public function testGetReturns_DataFromDiXml(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
            restAuthKey: 'klevu-rest-key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'klevu_configuration/developer/url_js',
            value: 'js.klevu.com',
            storeCode: $storeFixture->getCode(),
        );

        $provider = $this->objectManager->get(RelLinkProviderInterface::class);
        $relLinks = $provider->get();

        $this->assertCount(expectedCount: 1, haystack: $relLinks);
        $this->assertContains(
            needle: [
                RelLinkProviderInterface::RESOURCE_PATH => 'https://js.klevu.com',
                RelLinkProviderInterface::RESOURCE_TYPE => 'preconnect',
            ],
            haystack: $relLinks,
        );
    }
}
