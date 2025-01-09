<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider;

use Klevu\Frontend\Model\Config\Source\KlevuCustomOptionsTypeSource;
use Klevu\Frontend\Service\Provider\SettingsProvider;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \Klevu\Frontend\Service\Provider\SettingsProvider
 * @magentoAppArea frontend
 */
class SettingsProviderTest extends TestCase
{
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;

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

        $this->implementationFqcn = SettingsProvider::class;
        $this->interfaceFqcn = SettingsProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsDefault_WhenNoDataSet(): void
    {
        $provider = $this->instantiateTestObject([
            'configSettingPath' => 'klevu_frontend/general/use_customer_groups',
            'returnType' => KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
        ]);
        $this->assertFalse(condition: $provider->get());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/klevu/core_config_path/setting true
     * @magentoConfigFixture default_store klevu/core_config_path/setting true
     */
    public function testGet_ReturnsCoreConfigData_AsBoolean(): void
    {
        $provider = $this->instantiateTestObject([
            'configSettingPath' => 'klevu/core_config_path/setting',
            'returnType' => KlevuCustomOptionsTypeSource::TYPE_VALUE_BOOLEAN,
        ]);
        $this->assertTrue($provider->get());
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/klevu/core_config_path/setting 12
     * @magentoConfigFixture default_store klevu/core_config_path/setting 12
     */
    public function testGet_ReturnsCoreConfigData_AsInteger(): void
    {
        $provider = $this->instantiateTestObject([
            'configSettingPath' => 'klevu/core_config_path/setting',
            'returnType' => KlevuCustomOptionsTypeSource::TYPE_VALUE_INTEGER,
        ]);
        $this->assertSame(
            expected: 12,
            actual: $provider->get(),
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/klevu/core_config_path/setting value
     * @magentoConfigFixture default_store klevu/core_config_path/setting value
     */
    public function testGet_ReturnsCoreConfigData_AsString(): void
    {
        $provider = $this->instantiateTestObject([
            'configSettingPath' => 'klevu/core_config_path/setting',
            'returnType' => KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
        ]);
        $this->assertSame(
            expected: 'value',
            actual: $provider->get(),
        );
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/klevu/core_config_path/setting config_setting
     * @magentoConfigFixture default_store klevu/core_config_path/setting config_setting
     * @magentoConfigFixture default/klevu_frontend/general/klevu_settings {"_1692439469779_779":{"path":"section.Setting","type":"3","value":"custom_setting"}}
     * @magentoConfigFixture default_store klevu_frontend/general/klevu_settings {"_1692439469779_779":{"path":"section.Setting","type":"3","value":"custom_setting"}}
     */
    public function testGet_ReturnsCustomSetting_OverCoreConfigData(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $provider = $this->instantiateTestObject([
            'configSettingPath' => 'klevu/core_config_path/setting',
            'customSettingsPath' => 'section.Setting',
            'returnType' => KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
        ]);
        $this->assertSame(
            expected: 'custom_setting',
            actual: $provider->get(),
        );
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/klevu/core_config_path/setting config_setting
     * @magentoConfigFixture default_store klevu/core_config_path/setting config_setting
     * @magentoConfigFixture default/klevu_frontend/general/klevu_settings {"_1692439469779_779":{"path":"section.Setting","type":"1","value":"custom_setting"}}
     * @magentoConfigFixture default_store klevu_frontend/general/klevu_settings {"_1692439469779_779":{"path":"section.Setting","type":"1","value":"custom_setting"}}
     */
    public function testGet_ReturnsCoreConfigData_WhenCustomSettingBoolean(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method}, Error {error}',
                [
                    'method' => 'Klevu\Frontend\Service\Provider\SettingsProvider::get',
                    'error' => sprintf(
                        'Invalid Type set for path %s in Jsv2 Custom Settings. Expected String, received %s.',
                        'section.Setting',
                        'bool',
                    ),
                ],
            );

        $provider = $this->instantiateTestObject([
            'configSettingPath' => 'klevu/core_config_path/setting',
            'customSettingsPath' => 'section.Setting',
            'returnType' => KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
            'logger' => $mockLogger,
        ]);
        $this->assertSame(
            expected: 'config_setting',
            actual: $provider->get(),
        );
    }

    // phpcs:disable Generic.Files.LineLength.TooLong
    /**
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/klevu/core_config_path/setting config_setting
     * @magentoConfigFixture default_store klevu/core_config_path/setting config_setting
     * @magentoConfigFixture default/klevu_frontend/general/klevu_settings {"_1692439469779_779":{"path":"section.Setting","type":"2","value":"custom_setting"}}
     * @magentoConfigFixture default_store klevu_frontend/general/klevu_settings {"_1692439469779_779":{"path":"section.Setting","type":"2","value":"custom_setting"}}
     */
    public function testGet_ReturnsCoreConfigData_WhenCustomSettingString(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $mockLogger = $this->getMockBuilder(LoggerInterface::class)
            ->getMock();
        $mockLogger->expects($this->once())
            ->method('error')
            ->with(
                'Method: {method}, Error {error}',
                [
                    'method' => 'Klevu\Frontend\Service\Provider\SettingsProvider::get',
                    'error' => sprintf(
                        'Invalid Type set for path %s in Jsv2 Custom Settings. Expected String, received %s.',
                        'section.Setting',
                        'int',
                    ),
                ],
            );

        $provider = $this->instantiateTestObject([
            'configSettingPath' => 'klevu/core_config_path/setting',
            'customSettingsPath' => 'section.Setting',
            'returnType' => KlevuCustomOptionsTypeSource::TYPE_VALUE_STRING,
            'logger' => $mockLogger,
        ]);
        $this->assertSame(
            expected: 'config_setting',
            actual: $provider->get(),
        );
    }
}
