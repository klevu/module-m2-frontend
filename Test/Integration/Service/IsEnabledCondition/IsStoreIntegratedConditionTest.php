<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\IsEnabledCondition;

use Klevu\Configuration\Service\IsStoreIntegratedService;
use Klevu\Configuration\Service\Provider\ApiKeyProvider;
use Klevu\Configuration\Service\Provider\AuthKeyProvider;
use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Service\IsEnabledCondition\IsStoreIntegratedCondition;
use Klevu\FrontendApi\Service\IsEnabledCondition\IsEnabledConditionInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Website\WebsiteFixturesPool;
use Klevu\TestFixtures\Website\WebsiteTrait;
use Magento\Framework\App\Config\Storage\Writer as ConfigWriter;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Klevu\Configuration\Service\IsStoreIntegratedService
 */
class IsStoreIntegratedConditionTest extends TestCase
{
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;
    use WebsiteTrait;

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

        $this->implementationFqcn = IsStoreIntegratedCondition::class;
        $this->interfaceFqcn = IsEnabledConditionInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
        $this->websiteFixturesPool = $this->objectManager->get(WebsiteFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->storeFixturesPool->rollback();
        $this->websiteFixturesPool->rollback();
    }

    public function testExecute_ReturnsFalse_AtGlobalScope(): void
    {
        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse($service->execute());
    }

    public function testExecute_ReturnsFalse_AtWebsiteScope(): void
    {
        $this->createWebsite();
        $websiteFixture = $this->websiteFixturesPool->get('test_website');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($websiteFixture->get());

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse($service->execute());
    }

    public function testExecute_ReturnsFalse_AtStoreScope_NotIntegrated(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse($service->execute());
    }

    public function testExecute_ReturnsFalse_AtStoreScope_OnlyJsApiKey(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            jsApiKey: 'klevu-js-key',
        );

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse($service->execute());
    }

    public function testExecute_ReturnsFalse_AtStoreScope_OnlyRestAuthKey(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->setAuthKeys(
            scopeProvider: $scopeProvider,
            restAuthKey: 'klevu-rest-key',
        );

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertFalse($service->execute());
    }

    public function testExecute_ReturnsTrue_AtStoreScope_WhenIntegrated(): void
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

        /** @var IsStoreIntegratedService $service */
        $service = $this->instantiateTestObject();
        $this->assertTrue($service->execute());
    }

    /**
     * @param ScopeProviderInterface $scopeProvider
     * @param string|null $jsApiKey
     * @param string|null $restAuthKey
     *
     * @return void
     */
    private function setAuthKeys(
        ScopeProviderInterface $scopeProvider,
        ?string $jsApiKey = null,
        ?string $restAuthKey = null,
    ): void {
        /** @var ConfigWriter $configWriter */
        $configWriter = $this->objectManager->get(ConfigWriter::class);
        /** @var StoreManagerInterface $storeManager */
        $storeManager = $this->objectManager->get(StoreManagerInterface::class);
        foreach ($storeManager->getWebsites() as $website) {
            $configWriter->delete(
                path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
                scope: ScopeInterface::SCOPE_WEBSITES,
                scopeId: $website->getId(),
            );
            $configWriter->delete(
                path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                scope: ScopeInterface::SCOPE_WEBSITES,
                scopeId: $website->getId(),
            );
        }
        foreach ($storeManager->getStores() as $store) {
            $configWriter->delete(
                path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
                scope: ScopeInterface::SCOPE_STORES,
                scopeId: $store->getId(),
            );
            $configWriter->delete(
                path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                scope: ScopeInterface::SCOPE_STORES,
                scopeId: $store->getId(),
            );
        }
        $scope = $scopeProvider->getCurrentScope();
        if (null !== $jsApiKey) {
            $configWriter->save(
                path: ApiKeyProvider::CONFIG_XML_PATH_JS_API_KEY,
                value: $jsApiKey,
                scope: $scope->getScopeType(),
                scopeId: $scope->getScopeId(),
            );
        }
        if (null !== $restAuthKey) {
            $configWriter->save(
                path: AuthKeyProvider::CONFIG_XML_PATH_REST_AUTH_KEY,
                value: $restAuthKey,
                scope: $scope->getScopeType(),
                scopeId: $scope->getScopeId(),
            );
        }
    }
}
