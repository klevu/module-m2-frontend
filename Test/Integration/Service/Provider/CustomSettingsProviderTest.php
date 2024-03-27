<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Exception\InvalidCustomSettingValueException;
use Klevu\Frontend\Service\Provider\CustomSettingsProvider;
use Klevu\FrontendApi\Service\Provider\CustomSettingsProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class CustomSettingsProviderTest extends TestCase
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

        $this->implementationFqcn = CustomSettingsProvider::class;
        $this->interfaceFqcn = CustomSettingsProviderInterface::class;
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
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/general/klevu_settings {"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"
     */
    public function testGet_ThrowsException_InvalidData(): void
    {
        // phpcs:enable Generic.Files.LineLength.TooLong
        $this->expectException(InvalidCustomSettingValueException::class);
        $this->expectExceptionMessage(
            'The data stored in klevu_frontend/general/klevu_settings could not be unserialized. '
            . 'Method: Klevu\Frontend\Service\Provider\CustomSettingsProvider::get. '
            . 'Error: Unable to unserialize value. Error: Syntax error',
        );

        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject();
        $provider->get();
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testGet_ReturnsEmptyArray_WhenNoValueSet(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject();
        $customSettings = $provider->get();

        $this->assertCount(expectedCount: 0, haystack: $customSettings);
    }

    /**
     * @magentoConfigFixture klevu_test_store_1_store klevu_frontend/general/klevu_settings {"_1691134650159_159":{"path":"search.showQuickOnEnter","type":"1","value":"true"},"_1691134896326_326":{"path":"console.level","type":"2","value":"4"},"_1691134906285_285":{"path":"url.queryParam","type":"3","value":"query"},"_1691134702373_373":{"path":"search.maxChars","type":"2","value":"256"},"_1691413022481_481":{"path":"search.placeholder","type":"3","value":"Override"}}
     */
    public function testGet_ReturnsDataForCurrentStore(): void
    {
        $this->createStore();
        $store = $this->storeFixturesPool->get('test_store');

        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($store->get());

        $provider = $this->instantiateTestObject();
        $customSettings = $provider->get();

        $this->assertCount(expectedCount: 5, haystack: $customSettings);

        $this->assertArrayHasKey(key: '_1691134650159_159', array: $customSettings);
        $data = $customSettings['_1691134650159_159'];
        $this->assertArrayHasKey(key: 'path', array: $data);
        $this->assertSame(expected: 'search.showQuickOnEnter', actual: $data['path']);
        $this->assertArrayHasKey(key: 'type', array: $data);
        $this->assertSame(expected: '1', actual: $data['type']);
        $this->assertArrayHasKey(key: 'value', array: $data);
        $this->assertSame(expected: 'true', actual: $data['value']);
    }
}
