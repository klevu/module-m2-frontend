<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel\Html\Head;

use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Frontend\Exception\InvalidJsIncludeConfigurationException;
use Klevu\Frontend\Service\Provider\Urls\JsUrlProvider;
use Klevu\Frontend\ViewModel\Html\Head\JsIncludes;
use Klevu\FrontendApi\ViewModel\JsIncludesInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\State as AppState;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers JsIncludes
 * @magentoAppArea frontend
 */
class JsIncludesTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;

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

        $this->implementationFqcn = JsIncludes::class;
        $this->interfaceFqcn = JsIncludesInterface::class;
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

    public function testGetLinks_ReturnsPath_WhenProviderNotSet(): void
    {
        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject([
            'jsIncludes' => [
                [
                    JsIncludes::RESOURCE_PATH => 'https://test.com',
                ],
            ],
        ]);

        $links = $viewModel->getLinks();
        $this->assertCount(expectedCount: 1, haystack: $links);
        $this->assertSame(expected: 'https://test.com', actual: array_shift($links));
    }

    public function testGetLinks_SkipsLinksAndLogsError_WhenPathInvalid(): void
    {
        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_PRODUCTION);

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method}, Error: {error}',
                [
                    'method' => 'Klevu\Frontend\ViewModel\Html\Head\JsIncludes::getLinks',
                    'error' => sprintf(
                        'Invalid Data provided for JsIncludes configuration: Either Link %s must begin with '
                        . '"https://" or %s must be set. Received {"path":"invalid.url"}.',
                        JsIncludes::RESOURCE_PATH,
                        JsIncludes::RESOURCE_PROVIDER,
                    ),
                ],
            );
        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'appState' => $mockAppState,
            'jsIncludes' => [
                [
                    JsIncludes::RESOURCE_PATH => 'invalid.url',
                ],
                [
                    JsIncludes::RESOURCE_PATH => 'https://test.com',
                ],
            ],
        ]);

        $links = $viewModel->getLinks();
        $this->assertCount(expectedCount: 1, haystack: $links);
        $this->assertSame(expected: 'https://test.com', actual: array_shift($links));
    }

    public function testGetLinks_ThrowsException_WhenPathInvalid_NotProductionMode(): void
    {
        $this->expectException(InvalidJsIncludeConfigurationException::class);
        $this->expectExceptionMessageMatches(
            '#Invalid Data provided for JsIncludes configuration: .*#',
        );

        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_DEVELOPER);

        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject([
            'appState' => $mockAppState,
            'jsIncludes' => [
                [
                    JsIncludes::RESOURCE_PATH => 'invalid.url',
                ],
                [
                    JsIncludes::RESOURCE_PATH => 'https://test.com',
                ],
            ],
        ]);
        $viewModel->getLinks();
    }

    public function testGetLinks_SkipsLinksAndLogsError_WithMissingPath(): void
    {
        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->exactly(2))
            ->method('getMode')
            ->willReturn(AppState::MODE_PRODUCTION);

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->exactly(2))
            ->method('error')
            ->withConsecutive(
                [
                    'Method: {method}, Error: {error}',
                    [
                        'method' => 'Klevu\Frontend\ViewModel\Html\Head\JsIncludes::getLinks',
                        'error' => 'Invalid Data provided for JsIncludes configuration: '
                            . 'Link path must be a none empty string. Received null ({"provider":"test"}).',
                    ],
                ],
                [
                    'Method: {method}, Error: {error}',
                    [
                        'method' => 'Klevu\Frontend\ViewModel\Html\Head\JsIncludes::getLinks',
                        'error' => 'Invalid Data provided for JsIncludes configuration: '
                            . 'Link path must be a none empty string. Received null ({"provider":{}}).',
                    ],
                ],
            );
        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'appState' => $mockAppState,
            'jsIncludes' => [
                [
                    JsIncludes::RESOURCE_PROVIDER => 'test',
                ],
                [
                    JsIncludes::RESOURCE_PROVIDER => $this->objectManager->create(JsUrlProvider::class),
                ],
                [
                    JsIncludes::RESOURCE_PATH => 'https://test.com/path',
                ],
            ],
        ]);

        $links = $viewModel->getLinks();
        $this->assertCount(expectedCount: 1, haystack: $links);
        $this->assertSame(expected: 'https://test.com/path', actual: array_shift($links));
    }

    public function testGetLinks_ThrowsException_WithMissingPath_NotProductionMode(): void
    {
        $this->expectException(InvalidJsIncludeConfigurationException::class);
        $this->expectExceptionMessageMatches(
            '#Invalid Data provided for JsIncludes configuration: .*#',
        );

        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_DEVELOPER);

        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject([
            'appState' => $mockAppState,
            'jsIncludes' => [
                [
                    JsIncludes::RESOURCE_PROVIDER => 'test',
                ],
                [
                    JsIncludes::RESOURCE_PROVIDER => $this->objectManager->create(JsUrlProvider::class),
                ],
                [
                    JsIncludes::RESOURCE_PATH => 'https://test.com/path',
                ],
            ],
        ]);
        $viewModel->getLinks();
    }

    public function testGetLinks_CombinesProviderAndPath_WhenProviderIsString(): void
    {
        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject([
            'jsIncludes' => [
                [
                    JsIncludes::RESOURCE_PATH => '/path/to/thing',
                    JsIncludes::RESOURCE_PROVIDER => 'https://some.url/',
                ],
                [
                    JsIncludes::RESOURCE_PATH => 'another/path',
                    JsIncludes::RESOURCE_PROVIDER => 'https://some.url',
                ],
                [
                    JsIncludes::RESOURCE_PATH => '/slash/on/end/',
                    JsIncludes::RESOURCE_PROVIDER => 'https://some.url',
                ],
            ],
        ]);

        $links = $viewModel->getLinks();
        $this->assertCount(expectedCount: 3, haystack: $links);
        $this->assertContains(needle: 'https://some.url/path/to/thing', haystack: $links);
        $this->assertContains(needle: 'https://some.url/another/path', haystack: $links);
        $this->assertContains(needle: 'https://some.url/slash/on/end/', haystack: $links);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_js some.url
     */
    public function testGetLinks_CombinesProviderAndPath_WhenProviderIsClass(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject([
            'jsIncludes' => [
                [
                    JsIncludes::RESOURCE_PATH => '/path/to/thing',
                    JsIncludes::RESOURCE_PROVIDER => $this->objectManager->get(JsUrlProvider::class),
                ],
                [
                    JsIncludes::RESOURCE_PATH => 'another/path',
                    JsIncludes::RESOURCE_PROVIDER => $this->objectManager->get(JsUrlProvider::class),
                ],
            ],
        ]);

        $links = $viewModel->getLinks();
        $this->assertCount(expectedCount: 2, haystack: $links);
        $this->assertContains(needle: 'https://some.url/path/to/thing', haystack: $links);
        $this->assertContains(needle: 'https://some.url/another/path', haystack: $links);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture klevu_test_store_1_store klevu_configuration/developer/url_js some.url/with/existing/path/
     */
    public function testGetLinks_CombinesProviderAndPath_WhenProviderIsClass_ProviderHasExistingPath(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        /** @var JsIncludes $viewModel */
        $viewModel = $this->instantiateTestObject([
            'jsIncludes' => [
                [
                    JsIncludes::RESOURCE_PATH => '/path/to/thing',
                    JsIncludes::RESOURCE_PROVIDER => $this->objectManager->get(JsUrlProvider::class),
                ],
                [
                    JsIncludes::RESOURCE_PATH => 'another/path',
                    JsIncludes::RESOURCE_PROVIDER => $this->objectManager->get(JsUrlProvider::class),
                ],
            ],
        ]);

        $links = $viewModel->getLinks();
        $this->assertCount(expectedCount: 2, haystack: $links);
        $this->assertContains(needle: 'https://some.url/with/existing/path/path/to/thing', haystack: $links);
        $this->assertContains(needle: 'https://some.url/with/existing/path/another/path', haystack: $links);
    }
}
