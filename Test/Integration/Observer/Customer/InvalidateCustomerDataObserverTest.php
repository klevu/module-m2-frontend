<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Observer\Customer;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\Frontend\Observer\Customer\InvalidateCustomerDataObserver;
use Klevu\Frontend\Observer\Framework\View\Layout\RelLinkBuilder;
use Klevu\FrontendApi\Service\Provider\CookieProviderInterface;
use Klevu\TestFixtures\Customer\CustomerTrait;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\ConfigInterface as EventConfig;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Customer\CustomerFixturePool;

/**
 * @covers \Klevu\Frontend\Observer\Customer\InvalidateCustomerDataObserver
 * @magentoAppArea frontend
 */
class InvalidateCustomerDataObserverTest extends TestCase
{
    use CustomerTrait;
    use ObjectInstantiationTrait;
    use StoreTrait;
    use TestImplementsInterfaceTrait;

    private const OBSERVER_NAME = 'Klevu_Frontend_invalidateCustomerDataOnLoginAndLogout';
    private const EVENT_NAME_CUSTOMER_LOGOUT = 'customer_logout';
    private const EVENT_NAME_CUSTOMER_LOGIN = 'customer_login';
    private const EVENT_NAMES = [
        self::EVENT_NAME_CUSTOMER_LOGOUT,
        self::EVENT_NAME_CUSTOMER_LOGIN,
    ];

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

        $this->implementationFqcn = RelLinkBuilder::class;
        $this->interfaceFqcn = ObserverInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerFixturePool = $this->objectManager->get(CustomerFixturePool::class);
        $this->storeFixturesPool = $this->objectManager->get(StoreFixturesPool::class);
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function tearDown(): void
    {
        $this->customerFixturePool->rollback();
        $this->storeFixturesPool->rollback();
    }

    public function testInvalidateCustomerDataObserver_IsConfigured(): void
    {
        $observerConfig = $this->objectManager->create(type: EventConfig::class);
        foreach (self::EVENT_NAMES as $eventName) {
            $observers = $observerConfig->getObservers(eventName: $eventName);

            $this->assertArrayHasKey(key: self::OBSERVER_NAME, array: $observers);
            $this->assertSame(
                expected: ltrim(string: InvalidateCustomerDataObserver::class, characters: '\\'),
                actual: $observers[self::OBSERVER_NAME]['instance'],
            );
        }
    }

    public function testCookieIsInvalidated_OnLogOut(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->clearCookie($storeFixture->getCode());
        $this->createCustomer();
        $customerFixture = $this->customerFixturePool->get('test_customer');
        $customer = $customerFixture->getCustomer();

        $cookieProvider = $this->objectManager->get(CookieProviderInterface::class);
        $cookie = $cookieProvider->get('klv_mage_' . $storeFixture->getCode());
        $this->assertNull(
            actual: $cookie['expire_sections']['customerData'] ?? null,
        );

        $this->dispatchEvent(
            event: self::EVENT_NAME_CUSTOMER_LOGOUT,
            customer: $customer,
        );

        $cookie = $cookieProvider->get('klv_mage_' . $storeFixture->getCode());
        $this->assertSame(
            expected: -1,
            actual: $cookie['expire_sections']['customerData'] ?? null,
        );
    }

    public function testCookieIsInvalidated_OnLogIn(): void
    {
        $this->createStore();
        $storeFixture = $this->storeFixturesPool->get('test_store');
        $scopeProvider = $this->objectManager->get(ScopeProviderInterface::class);
        $scopeProvider->setCurrentScope($storeFixture->get());

        $this->clearCookie($storeFixture->getCode());
        $this->createCustomer();
        $customerFixture = $this->customerFixturePool->get('test_customer');
        $customer = $customerFixture->getCustomer();

        $cookieProvider = $this->objectManager->get(CookieProviderInterface::class);
        $cookie = $cookieProvider->get('klv_mage_' . $storeFixture->getCode());
        $this->assertNull(
            actual: $cookie['expire_sections']['customerData'] ?? null,
        );

        $this->dispatchEvent(
            event: self::EVENT_NAME_CUSTOMER_LOGIN,
            customer: $customer,
        );

        $cookie = $cookieProvider->get('klv_mage_' . $storeFixture->getCode());
        $this->assertSame(
            expected: -1,
            actual: $cookie['expire_sections']['customerData'] ?? null,
        );
    }

    /**
     * @param string $event
     * @param CustomerInterface $customer
     *
     * @return void
     */
    private function dispatchEvent(
        string $event,
        CustomerInterface $customer,
    ): void {
        /** @var EventManager $eventManager */
        $eventManager = $this->objectManager->get(type: EventManager::class);
        $eventManager->dispatch(
            $event,
            [
                'customer' => $customer,
            ],
        );
    }

    /**
     * @param string $storeCode
     *
     * @return void
     * @throws FailureToSendException
     * @throws InputException
     */
    private function clearCookie(string $storeCode = ''): void
    {
        $cookieManager = $this->objectManager->get(CookieManagerInterface::class);
        $cookieManager->deleteCookie('klv_mage_' . $storeCode);
    }
}
