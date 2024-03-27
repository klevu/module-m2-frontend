<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\WebApi;

use Klevu\Customer\Service\Provider\CustomerSessionProviderInterface;
use Klevu\Frontend\HTTP\PhpEnvironment\RemoteAddress as RemoteAddressVirtualType;
use Klevu\Frontend\WebApi\CustomerDataProvider;
use Klevu\FrontendApi\Api\CustomerDataProviderInterface;
use Klevu\TestFixtures\Customer\CustomerTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Customer\CustomerFixture;
use TddWizard\Fixtures\Customer\CustomerFixturePool;

/**
 * @covers \Klevu\Frontend\WebApi\CustomerDataProvider
 */
class CustomerDataProviderTest extends TestCase
{
    use CustomerTrait;
    use ObjectInstantiationTrait;
    use TestImplementsInterfaceTrait;
    use TestInterfacePreferenceTrait;

    private const HASH_SHA_256 = 'sha256';

    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $objectManager = null;
    /**
     * @var MockObject|SessionManagerInterface
     */
    private MockObject|SessionManagerInterface $mockSessionManager;
    /**
     * @var string[]|false
     */
    private array|false $keys;

    /**
     * @return void
     * @throws FileSystemException
     * @throws RuntimeException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->implementationFqcn = CustomerDataProvider::class;
        $this->interfaceFqcn = CustomerDataProviderInterface::class;
        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerFixturePool = $this->objectManager->get(CustomerFixturePool::class);
        $this->mockSessionManager = $this->getMockBuilder(SessionManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $deploymentConfig = $this->objectManager->get(DeploymentConfig::class);
        $this->keys = preg_split(
            pattern: '/\s+/s',
            subject: trim((string)$deploymentConfig->get(Encryptor::PARAM_CRYPT_KEY)),
        );
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->customerFixturePool->rollback();
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea webapi_rest
     */
    public function testExecute_ReturnsCustomerData_WhenNotLoggedIn(): void
    {
        $sessionId = '20945024957';
        $this->setSessionId($sessionId);

        $remoteAddress = '192.234.543.194';
        $this->setRemoteAddress($remoteAddress);

        /** @var CustomerDataProvider $api */
        $api = $this->instantiateTestObject();
        $customerData = $api->get();

        $expectedHash = hash_hmac(
            algo: self::HASH_SHA_256,
            data: $sessionId,
            key: $this->keys[count($this->keys) - 1],
        );
        $this->assertSame(
            expected: $expectedHash,
            actual: $customerData->getSessionId(),
        );
        $this->assertSame(
            expected: $remoteAddress,
            actual: $customerData->getShopperIp(),
            message: 'Remote Address',
        );
        $this->assertNull(actual: $customerData->getCustomerGroupId(), message: 'Group ID');
        $this->assertNull(actual: $customerData->getIdCode(), message: 'Customer Email Hash');
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoAppArea webapi_rest
     */
    public function testExecute_ReturnsCustomerData_WhenLoggedIn(): void
    {
        $sessionId = '2490592452';
        $this->setSessionId($sessionId);

        $remoteAddress = '625.825.834.842';
        $this->setRemoteAddress($remoteAddress);

        $this->createCustomer();
        $customer = $this->customerFixturePool->get('test_customer');
        $this->setCustomerInSession($customer);

        /** @var CustomerDataProvider $api */
        $api = $this->instantiateTestObject();
        $customerData = $api->get();

        $expectedHash = hash_hmac(
            algo: self::HASH_SHA_256,
            data: $sessionId,
            key: $this->keys[count($this->keys) - 1],
        );
        $this->assertSame(
            expected: $expectedHash,
            actual: $customerData->getSessionId(),
            message: 'Session Id Hash',
        );
        $this->assertSame(
            expected: $remoteAddress,
            actual: $customerData->getShopperIp(),
            message: 'Remote Address',
        );
        $this->assertSame(
            expected: 1,
            actual: $customerData->getCustomerGroupId(),
            message: 'Group ID',
        );
        $expectedEmailHash = 'cep-' .
            hash_hmac(
                algo: self::HASH_SHA_256,
                data: $customer->getEmail(),
                key: $this->keys[count($this->keys) - 1],
            );
        $this->assertSame(
            expected: $expectedEmailHash,
            actual: $customerData->getIdCode(),
            message: 'Customer Email Hash',
        );
    }

    /**
     * @param mixed $sessionId
     *
     * @return void
     */
    private function setSessionId(mixed $sessionId): void
    {
        $this->mockSessionManager->expects($this->once())
            ->method('getSessionId')
            ->willReturn($sessionId);
        $customerSessionProvider = $this->objectManager->create(
            type: CustomerSessionProviderInterface::class,
            arguments: [
                'sessionManager' => $this->mockSessionManager,
            ],
        );
        $this->objectManager->addSharedInstance(
            instance: $customerSessionProvider,
            className: CustomerSessionProviderInterface::class,
            forPreference: true,
        );
    }

    /**
     * @param CustomerFixture $customer
     *
     * @return void
     */
    private function setCustomerInSession(CustomerFixture $customer): void
    {
        $mockSessionManager = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSessionManager->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customer->getId());
        $this->objectManager->addSharedInstance(
            instance: $mockSessionManager,
            className: CustomerSession::class,
            forPreference: true,
        );
    }

    /**
     * @param mixed $remoteAddress
     *
     * @return void
     */
    private function setRemoteAddress(mixed $remoteAddress): void
    {
        $mockRemoteAddress = $this->getMockBuilder(RemoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockRemoteAddress->method('getRemoteAddress')
            ->willReturn($remoteAddress);
        $this->objectManager->addSharedInstance(
            instance: $mockRemoteAddress,
            className: RemoteAddressVirtualType::class, // @phpstan-ignore-line
        );
    }
}
