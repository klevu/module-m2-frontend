<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Exception\InvalidCustomSettingValueException;
use Klevu\Frontend\Exception\InvalidSettingsProviderConfigurationException;
use Klevu\Frontend\Service\IsEnabledCondition\IsStoreIntegratedCondition;
use Klevu\Frontend\Service\KlevuSettingsBuilder;
use Klevu\FrontendApi\Service\KlevuSettingsBuilderInterface;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\SetAuthKeysTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\App\State as AppState;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers \Klevu\Frontend\Service\KlevuSettingsBuilder
 */
class KlevuSettingsBuilderTest extends TestCase
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

        $this->implementationFqcn = KlevuSettingsBuilder::class;
        $this->interfaceFqcn = KlevuSettingsBuilderInterface::class;
        $this->constructorArgumentDefaults = [
            'klevuSettings' => [],
            'isEnabledConditions' => [],
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

    /**
     * @magentoAppArea frontend
     */
    public function testExecute_ThrowsInvalidArgumentException_WhenInvalidCustomKlevuSettings(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $this->expectException(InvalidCustomSettingValueException::class);
        $this->expectExceptionMessage(
            'The data stored in klevu_frontend/general/klevu_settings could not be unserialized. ' .
            'Method: Klevu\Frontend\Service\Provider\CustomSettingsProvider::get. ' .
            'Error: Unable to unserialize value. Error: Syntax error',
        );

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
            value: '{"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"',
            storeCode: $storeFixture->getCode(),
        );

        $service = $this->instantiateTestObject();
        $service->execute();
    }

    /**
     * @magentoAppArea frontend
     */
    public function testExecute_ThrowsInvalidSettingsProviderConfigurationException_WhenInvalidSettingsProvider(): void
    {
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
        $this->expectException(InvalidSettingsProviderConfigurationException::class);
        $this->expectExceptionMessage($errorMessage);

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

        $service = $this->instantiateTestObject([
            'klevuSettings' => [
                'global' => [
                    'apiKey' => $invalidSettingProvider,
                ],
            ],
        ]);
        $klevuSettings = $service->execute();

        $this->assertJson(actualJson: $klevuSettings);
        $this->assertSame(expected: '[]', actual: $klevuSettings);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testExecute_LogsError_WhenInvalidSettingsProvider_ProductionMode(): void
    {
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
            path: 'klevu_configuration/developer/url_search',
            value: 'klevu-search.url',
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'klevu_frontend/quick_search/enabled',
            value: 1,
            storeCode: $storeFixture->getCode(),
        );
        ConfigFixture::setForStore(
            path: 'general/locale/code',
            value: 'en_US',
            storeCode: $storeFixture->getCode(),
        );

        $service = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'appState' => $mockAppState,
            'klevuSettings' => [
                'global' => [
                    'apiKey' => $invalidSettingProvider,
                ],
            ],
        ]);
        $klevuSettings = $service->execute();

        $this->assertNotNull(actual: $klevuSettings);
        $this->assertJson(actualJson: $klevuSettings);
        $this->assertSame(expected: '{"global":[]}', actual: $klevuSettings);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testExecute_ReturnsJsonObject(): void
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
            // phpcs:ignore Generic.Files.LineLength.TooLong
            value: '{"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"},"_1691134896326_326":{"path":"console.level","type":"2","value":"4"},"_1691134906285_285":{"path":"url.queryParam","type":"3","value":"query"},"_1691134702373_373":{"path":"search.maxChars","type":"2","value":"256"},"_1691413022481_481":{"path":"search.placeholder","type":"3","value":"Override"}}',
            storeCode: $storeFixture->getCode(),
        );

        $service = $this->instantiateTestObject([
            'isEnabledConditions' => [
                'klevu_integrated' => $this->objectManager->get(IsStoreIntegratedCondition::class),
            ],
        ]);
        $klevuSettings = $service->execute();

        $this->assertJson(actualJson: $klevuSettings);
        $this->assertStringContainsString(
            needle: '"global":{"apiKey":"klevu_js_api_key","language":"en"}',
            haystack: $klevuSettings,
        );
        $this->assertStringNotMatchesFormat(
            format: '"search":{%A"placeholder":"Override"%A}',
            string: $klevuSettings,
        );
        $this->assertStringNotMatchesFormat(
            format: '"search":{%A"maxChars":256%A}',
            string: $klevuSettings,
        );
        $this->assertStringNotMatchesFormat(
            format: '"search":{%A"showQuickOnEnter":true%A}',
            string: $klevuSettings,
        );
        $this->assertStringNotMatchesFormat(
            format: '"url":{%A"queryParam":"query"%A}',
            string: $klevuSettings,
        );
        $this->assertStringNotMatchesFormat(
            format: '"url":{%A"search":"https:\/\/klevu-search.url\/cs\/v2\/search"%A}',
            string: $klevuSettings,
        );
        $this->assertStringNotMatchesFormat(
            format: '"url":{%A"landing":"\/catalogsearch\/result"%A}',
            string: $klevuSettings,
        );
        $this->assertStringContainsString(
            needle: '"console":{"level":4}',
            haystack: $klevuSettings,
        );
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store general/locale/code en_US
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/general/klevu_settings {"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"},"_1691134896326_326":{"path":"console.level","type":"2","value":"4"},"_1691134906285_285":{"path":"url.queryParam","type":"3","value":"query"},"_1691134702373_373":{"path":"search.maxChars","type":"2","value":"256"},"_1691413022481_481":{"path":"search.placeholder","type":"3","value":"Override"}}
     */
    public function testExecute_ReturnsNull_WhenNotIntegrated(): void
    {
        //phpcs:enable Generic.Files.LineLength.TooLong
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $service = $this->instantiateTestObject([
            'isEnabledConditions' => [
                'klevu_integrated' => $this->objectManager->get(IsStoreIntegratedCondition::class),
            ],
        ]);
        $klevuSettings = $service->execute();

        $this->assertNull(actual: $klevuSettings, message: 'Klevu Settings Null When not integrated');
    }
}
