<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Configuration\Service\Provider\StoreScopeProviderInterface;
use Klevu\Frontend\Block\Adminhtml\Form\Field\CustomSettings;
use Klevu\Frontend\Exception\InvalidSettingsProviderConfigurationException;
use Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource;
use Klevu\Frontend\Service\CustomSettingsBuilder;
use Klevu\FrontendApi\Service\CustomSettingsBuilderInterface;
use Klevu\FrontendApi\Service\Provider\CustomSettingsProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\App\State as AppState;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Klevu\Frontend\Service\CustomSettingsBuilder
 * @method CustomSettingsBuilderInterface instantiateTestObject(?array $arguments = null)
 * @method CustomSettingsBuilderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 */
class CustomSettingsBuilderTest extends TestCase
{
    use ObjectInstantiationTrait;
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

        $this->implementationFqcn = CustomSettingsBuilder::class;
        $this->interfaceFqcn = CustomSettingsBuilderInterface::class;
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

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/general/klevu_settings {"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"24","value":"true"}}
     */
    public function testExecute_ThrowsInvalidSettingsProviderConfigurationException_ForInvalidType(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $this->expectException(InvalidSettingsProviderConfigurationException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid setting type provided. Expected one of %s, received %s',
                implode(', ', [
                    KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
                    KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER,
                    KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
                ]),
                '24',
            ),
        );

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        $builder = $this->instantiateTestObject();
        $builder->execute();
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/general/klevu_settings {"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"24","value":"true"}}
     */
    public function testExecute_LogsError_ForInvalidType_ProductionMode(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $message = sprintf(
            'Invalid setting type provided. Expected one of %s, received %s',
            implode(', ', [
                KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
                KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER,
                KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
            ]),
            '24',
        );
        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method}, Error: {error}',
                ['method' => 'Klevu\Frontend\Service\CustomSettingsBuilder::execute', 'error' => $message],
            );

        $mockAppState = $this->getMockBuilder(AppState::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockAppState->expects($this->once())
            ->method('getMode')
            ->willReturn(AppState::MODE_PRODUCTION);

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        $builder = $this->instantiateTestObject([
            'logger' => $mockLogger,
            'appState' => $mockAppState,
        ]);
        $builder->execute();
    }

    /**
     * @magentoAppArea frontend
     * @dataProvider dataProvider_testExecute_ReturnsFalse_ForBooleanType_Falsey
     */
    public function testExecute_ReturnsFalse_ForBooleanType_Falsey(mixed $value): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $mockCustomSettingsProvider = $this->getMockBuilder(CustomSettingsProviderInterface::class)
            ->getMock();
        $mockCustomSettingsProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                0 => [
                    CustomSettings::COLUMN_NAME_PATH => 'search.showQuickOnEnter',
                    CustomSettings::COLUMN_NAME_TYPE => KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
                    CustomSettings::COLUMN_NAME_VALUE => $value,
                ],
            ]);

        $provider = $this->instantiateTestObject([
            'customSettingsProvider' => $mockCustomSettingsProvider,
        ]);
        $settings = $provider->execute();

        $this->assertArrayHasKey(key: 'search', array: $settings);
        $this->assertIsArray(actual: $settings['search']);
        $this->assertArrayHasKey(key: 'showQuickOnEnter', array: $settings['search']);
        $this->assertFalse(condition: $settings['search']['showQuickOnEnter']);
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testExecute_ReturnsFalse_ForBooleanType_Falsey(): array
    {
        return [
            [false],
            ['false'],
            [0],
            ['0'],
            [null],
        ];
    }

    /**
     * @magentoAppArea frontend
     * @dataProvider dataProvider_testExecute_ReturnsTrue_ForBooleanType_Truthy
     */
    public function testExecute_ReturnsTrue_ForBooleanType_Truthy(mixed $value): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $mockCustomSettingsProvider = $this->getMockBuilder(CustomSettingsProviderInterface::class)
            ->getMock();
        $mockCustomSettingsProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                0 => [
                    CustomSettings::COLUMN_NAME_PATH => 'search.showQuickOnEnter',
                    CustomSettings::COLUMN_NAME_TYPE => KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
                    CustomSettings::COLUMN_NAME_VALUE => $value,
                ],
            ]);

        $provider = $this->instantiateTestObject([
            'customSettingsProvider' => $mockCustomSettingsProvider,
        ]);
        $settings = $provider->execute();

        $this->assertArrayHasKey(key: 'search', array: $settings);
        $this->assertIsArray(actual: $settings['search']);
        $this->assertArrayHasKey(key: 'showQuickOnEnter', array: $settings['search']);
        $this->assertTrue(condition: $settings['search']['showQuickOnEnter']);
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testExecute_ReturnsTrue_ForBooleanType_Truthy(): array
    {
        return [
            [true],
            ['true'],
            ['string'],
            [1],
            ['1'],
            [1.23],
            ['1.23'],
            [-1],
        ];
    }

    /**
     * @magentoAppArea frontend
     * @dataProvider dataProvider_testExecute_ReturnsInt_ForIntType
     */
    public function testExecute_ReturnsInt_ForIntType(mixed $value): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $mockCustomSettingsProvider = $this->getMockBuilder(CustomSettingsProviderInterface::class)
            ->getMock();
        $mockCustomSettingsProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                0 => [
                    CustomSettings::COLUMN_NAME_PATH => 'search.minChars',
                    CustomSettings::COLUMN_NAME_TYPE => KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER,
                    CustomSettings::COLUMN_NAME_VALUE => $value,
                ],
            ]);

        $provider = $this->instantiateTestObject([
            'customSettingsProvider' => $mockCustomSettingsProvider,
        ]);
        $settings = $provider->execute();

        $this->assertArrayHasKey(key: 'search', array: $settings);
        $this->assertIsArray(actual: $settings['search']);
        $this->assertArrayHasKey(key: 'minChars', array: $settings['search']);
        $this->assertIsInt(actual: $settings['search']['minChars']);
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testExecute_ReturnsInt_ForIntType(): array
    {
        return [
            [null],
            [true],
            [false],
            ['12true'],
            ['123'],
            [0],
            [1],
            ['1'],
            [1.23],
            ['1.23'],
            [-1],
            ['string'],
        ];
    }

    /**
     * @magentoAppArea frontend
     * @dataProvider dataProvider_testExecute_ReturnsString_ForStringType
     */
    public function testExecute_ReturnsString_ForStringType(mixed $value): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $mockCustomSettingsProvider = $this->getMockBuilder(CustomSettingsProviderInterface::class)
            ->getMock();
        $mockCustomSettingsProvider->expects($this->once())
            ->method('get')
            ->willReturn([
                0 => [
                    CustomSettings::COLUMN_NAME_PATH => 'url.landing',
                    CustomSettings::COLUMN_NAME_TYPE => KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
                    CustomSettings::COLUMN_NAME_VALUE => $value,
                ],
            ]);

        $provider = $this->instantiateTestObject([
            'customSettingsProvider' => $mockCustomSettingsProvider,
        ]);
        $settings = $provider->execute();

        $this->assertArrayHasKey(key: 'url', array: $settings);
        $this->assertIsArray(actual: $settings['url']);
        $this->assertArrayHasKey(key: 'landing', array: $settings['url']);
        $this->assertIsString(actual: $settings['url']['landing']);
    }

    /**
     * @return mixed[][]
     */
    public function dataProvider_testExecute_ReturnsString_ForStringType(): array
    {
        return [
            [null],
            [true],
            [false],
            [0],
            [1],
            [1.23],
            [-1],
            ['string'],
        ];
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/general/klevu_settings {"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"},"_1691134896326_326":{"path":"console.level","type":"2","value":"4"},"_1691134906285_285":{"path":"url.queryParam","type":"3","value":"query"},"_1691134702373_373":{"path":"search.maxChars","type":"2","value":"256"}}
     */
    public function testExecute_BuildsCustomSettings(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        $builder = $this->instantiateTestObject();
        $settings = $builder->execute();

        $this->assertArrayHasKey(key: 'search', array: $settings);
        $this->assertIsArray(actual: $settings['search']);
        $this->assertArrayHasKey(key: 'showQuickOnEnter', array: $settings['search']);
        $this->assertTrue(condition: $settings['search']['showQuickOnEnter']);
        $this->assertArrayHasKey(key: 'maxChars', array: $settings['search']);
        $this->assertSame(expected: 256, actual: $settings['search']['maxChars']);

        $this->assertArrayHasKey(key: 'url', array: $settings);
        $this->assertIsArray(actual: $settings['url']);
        $this->assertArrayHasKey(key: 'queryParam', array: $settings['url']);
        $this->assertSame(expected: "query", actual: $settings['url']['queryParam']);

        $this->assertArrayHasKey(key: 'console', array: $settings);
        $this->assertIsArray(actual: $settings['console']);
        $this->assertArrayHasKey(key: 'level', array: $settings['console']);
        $this->assertSame(expected: 4, actual: $settings['console']['level']);
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea frontend
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/general/klevu_settings {"_1692973318288_288":{"path":"level1.level2.level3a.level4a","type":"3","value":"A Value"},"_1692973403771_771":{"path":"level1.level2.level3a.level4b","type":"2","value":"123"},"_1692974317570_570":{"path":"level1.level2.level3b.level4a","type":"1","value":"true"},"_1692974331554_554":{"path":"level1.level2.level3b.level4b","type":"2","value":"456"}}
     */
    public function testExecute_BuildsCustomSettings_MultiLevel(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(StoreScopeProviderInterface::class);
        $scopeProvider->setCurrentStore($store->get());

        $builder = $this->instantiateTestObject();
        $settings = $builder->execute();

        $this->assertCount(expectedCount: 1, haystack: $settings);
        $this->assertArrayHasKey(key: 'level1', array: $settings);
        $this->assertIsArray(actual: $settings['level1']);

        $this->assertCount(expectedCount: 1, haystack: $settings['level1']);
        $this->assertArrayHasKey(key: 'level2', array: $settings['level1']);
        $this->assertIsArray(actual: $settings['level1']['level2']);

        $this->assertCount(expectedCount: 2, haystack: $settings['level1']['level2']);
        $this->assertArrayHasKey(key: 'level3a', array: $settings['level1']['level2']);
        $this->assertIsArray(actual: $settings['level1']['level2']['level3a']);

        $this->assertCount(expectedCount: 2, haystack: $settings['level1']['level2']['level3a']);
        $this->assertArrayHasKey(key: 'level4a', array: $settings['level1']['level2']['level3a']);
        $this->assertSame(
            expected: "A Value",
            actual: $settings['level1']['level2']['level3a']['level4a'],
        );
        $this->assertArrayHasKey(key: 'level4b', array: $settings['level1']['level2']['level3a']);
        $this->assertSame(
            expected: 123,
            actual: $settings['level1']['level2']['level3a']['level4b'],
        );

        $this->assertCount(expectedCount: 2, haystack: $settings['level1']['level2']['level3b']);
        $this->assertArrayHasKey(key: 'level4a', array: $settings['level1']['level2']['level3b']);
        $this->assertTrue(condition: $settings['level1']['level2']['level3b']['level4a']);
        $this->assertArrayHasKey(key: 'level4b', array: $settings['level1']['level2']['level3b']);
        $this->assertSame(
            expected: 456,
            actual: $settings['level1']['level2']['level3b']['level4b'],
        );
    }
}
