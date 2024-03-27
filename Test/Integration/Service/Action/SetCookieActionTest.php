<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Action;

use Klevu\Frontend\Service\Action\SetCookieAction;
use Klevu\FrontendApi\Service\Action\SetCookieActionInterface;
use Klevu\TestFixtures\Store\StoreFixturesPool;
use Klevu\TestFixtures\Store\StoreTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Framework\Exception\InputException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class SetCookieActionTest extends TestCase
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

        $this->implementationFqcn = SetCookieAction::class;
        $this->interfaceFqcn = SetCookieActionInterface::class;
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

    public function testExecute_SetCookie(): void
    {
        $this->clearCookie();
        $cookieData = [
            'key1' => 94563,
            'key2' => true,
            'key_3' => [
                'thing1' => 123,
                'thing2' => false,
                'thing3' => 'some string',
            ],
            'key4' => 'another string',
        ];

        /** @var SetCookieActionInterface $action */
        $action = $this->instantiateTestObject();
        $action->execute(
            name: 'klv_mage',
            data: $cookieData,
        );

        $cookieManager = $this->objectManager->get(CookieManagerInterface::class);
        $cookie = $cookieManager->getCookie('klv_mage');
        $serializer = $this->objectManager->get(Json::class);

        $this->assertEquals(
            expected: $cookieData,
            actual: $serializer->unserialize($cookie),
        );
    }

    /**
     * @return void
     * @throws InputException
     * @throws FailureToSendException
     */
    private function clearCookie(): void
    {
        $cookieManager = $this->objectManager->get(CookieManagerInterface::class);
        $cookieManager->deleteCookie('klv_mage');
    }
}
