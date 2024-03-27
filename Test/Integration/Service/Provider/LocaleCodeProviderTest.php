<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\Provider\LocaleCodeProvider as LocaleCodeProviderVirtualType;
use Klevu\Frontend\Service\Provider\SettingsProvider;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Core\ConfigFixture;

/**
 * @covers SettingsProvider
 * @method SettingsProviderInterface instantiateTestObject(?array $arguments = null)
 * @method SettingsProviderInterface instantiateTestObjectFromInterface(?array $arguments = null)
 * @magentoAppArea frontend
 */
class LocaleCodeProviderTest extends TestCase
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

        $this->implementationFqcn = LocaleCodeProviderVirtualType::class; // @phpstan-ignore-line
        $this->interfaceFqcn = SettingsProviderInterface::class;
        $this->implementationForVirtualType = SettingsProvider::class;
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

    public function testGet_ReturnsSetting_ForGlobalScope(): void
    {
        ConfigFixture::setGlobal(
            path: 'general/locale/code',
            value: 'fr_FR',
        );

        $provider = $this->instantiateTestObject();
        $result = $provider->get();

        $this->assertSame(expected: 'fr_FR', actual: $result);
    }

    public function testGet_ReturnsSetting_ForStoreScope(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());
        ConfigFixture::setGlobal(
            path: 'general/locale/code',
            value: 'fr_FR',
        );
        ConfigFixture::setForStore(
            path: 'general/locale/code',
            value: 'de_DE',
            storeCode: $storeFixture->getCode(),
        );

        $provider = $this->instantiateTestObject();
        $result = $provider->get();

        $this->assertSame(expected: 'de_DE', actual: $result);
    }
}
