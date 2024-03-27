<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\ViewModel\Html\Head;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource;
use Klevu\Frontend\Service\CustomSettingsBuilder;
use Klevu\Frontend\Service\KlevuSettingsBuilder;
use Klevu\Frontend\ViewModel\Html\Head\JsSettings;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\FrontendApi\ViewModel\Html\Head\JsSettingsInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\App\State as AppState;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Frontend\ViewModel\Html\Head\JsSettings
 * @magentoAppArea frontend
 */
class JsSettingsTest extends TestCase
{
    use ObjectInstantiationTrait;
    use SetAuthKeysTrait;
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

        $this->implementationFqcn = JsSettings::class;
        $this->interfaceFqcn = JsSettingsInterface::class;
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

    public function testGetKlevuJsSettings_ReturnsError_ConfigurationInvalid(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/general/klevu_settings',
            value: '{"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"',
            storeCode: $storeFixture->getCode(),
        );

        $errorMessage = 'The data stored in klevu_frontend/general/klevu_settings could not be unserialized.'
            . ' Method: Klevu\Frontend\Service\Provider\CustomSettingsProvider::get.'
            . ' Error: Unable to unserialize value. Error: Syntax error';

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method}, Error: {error}',
                [
                    'method' => 'Klevu\Frontend\ViewModel\Html\Head\JsSettings::getKlevuJsSettings',
                    'error' => $errorMessage,
                ],
            );

        $klevuSettingsBuilder = $this->objectManager->get(type: KlevuSettingsBuilder::class);
        /** @var JsSettings $viewModel */
        $viewModel = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'klevuSettingsBuilder' => $klevuSettingsBuilder,
        ]);

        $this->assertEquals(
            expected: json_decode("{error: 'An error occurred while building Klevu Settings. See log for details.'}"),
            actual: json_decode($viewModel->getKlevuJsSettings()),
        );
    }

    public function testGetKlevuJsSettings_ReturnsJsonWithOutInvalidPair_ConfigurationInvalid_ProductionMode(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/general/klevu_settings',
            value: '{'
            . '"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"836","value":"true"},'
            . '"_1691134702373_373":{"path":"search.maxChars","type":"2","value":"256"}'
            . '}',
            storeCode: $storeFixture->getCode(),
        );

        $errorMessage = sprintf(
            'Invalid setting type provided. Expected one of %s, received %s',
            implode(', ', [
                KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
                KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER,
                KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
            ]),
            '836',
        );

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method}, Error: {error}',
                [
                    'method' => 'Klevu\Frontend\Service\CustomSettingsBuilder::execute',
                    'error' => $errorMessage,
                ],
            );

        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_PRODUCTION);

        $customSettingsBuilder = $this->objectManager->create(
            type: CustomSettingsBuilder::class,
            arguments: [
                'appState' => $mockAppState,
                'logger' => $mockLogger,
            ],
        );

        $klevuSettingsBuilder = $this->objectManager->create(
            type: KlevuSettingsBuilder::class,
            arguments: [
                'customSettingsBuilder' => $customSettingsBuilder,
                'appState' => $mockAppState,
                'klevuSettings' => [
                    'global' => [
                        'apiKey' => 'klevu-js-api-key',
                    ],
                    'search' => [
                        'minChars' => 5,
                    ],
                ],
            ],
        );
        /** @var JsSettings $viewModel */
        $viewModel = $this->instantiateTestObject([
            'klevuSettingsBuilder' => $klevuSettingsBuilder,
        ]);

        $this->assertEquals(
            expected: json_decode('{"global":{"apiKey":"klevu-js-api-key"},"search":{"maxChars":256,"minChars":5}}'),
            actual: json_decode($viewModel->getKlevuJsSettings()),
        );
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/quick_search/enabled 1
     */
    public function testGetKlevuJsSettings_ReturnsError_InvalidSettingsProvider(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );

        $invalidSettingProvider = $this->objectManager->create(DataObject::class);

        $errorMessage = sprintf(
            'Invalid Settings Provided. Expected one of (%s), received %s.',
            implode(
                separator: ', ',
                array: [
                    'array',
                    SettingsProviderInterface::class,
                    'scalar',
                ],
            ),
            get_debug_type($invalidSettingProvider),
        );

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method}, Error: {error}',
                [
                    'method' => 'Klevu\Frontend\ViewModel\Html\Head\JsSettings::getKlevuJsSettings',
                    'error' => $errorMessage,
                ],
            );

        $klevuSettingsBuilder = $this->objectManager->create(
            type: KlevuSettingsBuilder::class,
            arguments: [
                'logger' => $mockLogger,
                'klevuSettings' => [
                    'global' => [
                        'apiKey' => $invalidSettingProvider,
                    ],
                ],
            ],
        );
        /** @var JsSettings $viewModel */
        $viewModel = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'klevuSettingsBuilder' => $klevuSettingsBuilder,
        ]);

        $this->assertEquals(
            expected: json_decode(
                "{error: 'An error occurred while building Klevu Settings. See log for details.'}",
            ),
            actual: json_decode($viewModel->getKlevuJsSettings()),
        );
    }

    public function testGetKlevuJsSettings_ReturnsJsonWithOutInvalidPair_InvalidSettingsProvider_ProductionMode(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );

        $invalidSettingProvider = $this->objectManager->create(DataObject::class);

        $errorMessage = sprintf(
            'Invalid Settings Provided. Expected one of (%s), received %s.',
            implode(
                separator: ', ',
                array: [
                    'array',
                    SettingsProviderInterface::class,
                    'scalar',
                ],
            ),
            get_debug_type($invalidSettingProvider),
        );

        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->method('error')
            ->with(
                'Method: {method}, Error: {error}',
                [
                    'method' => 'Klevu\Frontend\Service\KlevuSettingsBuilder::processSettingsFromDiXml',
                    'error' => $errorMessage,
                ],
            );

        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_PRODUCTION);

        $klevuSettingsBuilder = $this->objectManager->create(
            type: KlevuSettingsBuilder::class,
            arguments: [
                'logger' => $mockLogger,
                'appState' => $mockAppState,
                'klevuSettings' => [
                    'global' => [
                        'apiKey' => $invalidSettingProvider,
                    ],
                    'search' => [
                        'minChars' => 5,
                    ],
                ],
            ],
        );
        /** @var JsSettings $viewModel */
        $viewModel = $this->instantiateTestObject([
            'klevuSettingsBuilder' => $klevuSettingsBuilder,
        ]);

        $expected = [
            'global' => [],
            'search' => [
                "minChars" => 5,
            ],
        ];
        $this->assertEquals(
            expected: json_decode(json_encode($expected)),
            actual: json_decode($viewModel->getKlevuJsSettings()),
        );
    }

    public function testGetKlevuJsSettings_ReturnsExpectedJson(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'klevu_configuration/developer/url_search',
            value: 'klevu-search.url',
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'general/locale/code',
            value: 'en_US',
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/general/klevu_settings',
            value: '{'
            . '"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"},'
            . '"_1691134896326_326":{"path":"console.level","type":"2","value":"4"},'
            . '"_1691134906285_285":{"path":"url.queryParam","type":"3","value":"query"},'
            . '"_1691134702373_373":{"path":"search.maxChars","type":"2","value":"256"},'
            . '"_1693833332396_396":{"path":"search.minChars","type":"2","value":"5"}'
            . '}',
            storeCode: $storeFixture->getCode(),
        );

        $viewModel = $this->instantiateTestObject();

        $result = json_decode(json: $viewModel->getKlevuJsSettings(), associative: true);
        $this->assertIsArray($result);

        $this->assertArrayHasKey(key: 'global', array: $result);
        $this->assertIsArray($result['global']);
        $this->assertArrayHasKey(key: 'apiKey', array: $result['global']);
        $this->assertSame(expected: 'klevu_js_api_key', actual: $result['global']['apiKey']);

        $this->assertArrayHasKey(key: 'language', array: $result['global']);
        $this->assertSame(expected: 'en', actual: $result['global']['language']);

        $this->assertArrayHasKey(key: 'url', array: $result);
        $this->assertIsArray($result['url']);
        $this->assertArrayHasKey(key: 'queryParam', array: $result['url']);
        $this->assertSame(expected: 'query', actual: $result['url']['queryParam']);
        $this->assertArrayHasKey(key: 'search', array: $result['url']);
        $this->assertSame(expected: 'https://klevu-search.url/cs/v2/search', actual: $result['url']['search']);
        $this->assertArrayHasKey(key: 'landing', array: $result['url']);
        $this->assertSame(expected: '/catalogsearch/result', actual: $result['url']['landing']);

        $this->assertArrayHasKey(key: 'search', array: $result);
        $this->assertIsArray($result['search']);
        $this->assertArrayHasKey(key: 'maxChars', array: $result['search']);
        $this->assertSame(expected: 256, actual: $result['search']['maxChars']);
        $this->assertArrayHasKey(key: 'showQuickOnEnter', array: $result['search']);
        $this->assertTrue(condition: $result['search']['showQuickOnEnter']);

        $this->assertArrayHasKey(key: 'console', array: $result);
        $this->assertIsArray($result['console']);
        $this->assertArrayHasKey(key: 'level', array: $result['console']);
        $this->assertSame(expected: 4, actual: $result['console']['level']);
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/quick_search/enabled 1
     */
    public function testisSettingsGenerationError_ReturnsTrue_ForErrors(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );

        $klevuSettingsBuilder = $this->objectManager->create(
            type: KlevuSettingsBuilder::class,
            arguments: [
                'klevuSettings' => [
                    'global' => [
                        'apiKey' => $this->objectManager->create(DataObject::class),
                    ],
                ],
            ],
        );
        /** @var JsSettings $viewModel */
        $viewModel = $this->instantiateTestObject([
            'klevuSettingsBuilder' => $klevuSettingsBuilder,
        ]);

        $this->assertTrue(condition: $viewModel->isSettingsGenerationError());
    }

    public function testisSettingsGenerationError_ReturnsFalse_NoErrors(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu_js_api_key',
            restAuthKey: 'klevu_rest_auth_key',
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'klevu_configuration/developer/url_search',
            value: 'klevu-search.url',
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'general/locale/code',
            value: 'en_US',
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/metadata/enabled',
            value: '{'
            . '"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"},'
            . '"_1691134896326_326":{"path":"console.level","type":"2","value":"4"},'
            . '"_1691134906285_285":{"path":"url.queryParam","type":"3","value":"query"},'
            . '"_1691134702373_373":{"path":"search.maxChars","type":"2","value":"256"}'
            . '}',
            storeCode: $storeFixture->getCode(),
        );

        $viewModel = $this->instantiateTestObject();

        $this->assertFalse(condition: $viewModel->isSettingsGenerationError());
    }
}
