<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Integration\Service\Provider\Customer;

use Klevu\Customer\Service\Provider\CustomerSessionProviderInterface;
use Klevu\Frontend\Service\Provider\Customer\CustomerDataProvider;
use Klevu\FrontendApi\Service\Provider\Customer\CustomerDataProviderInterface;
use Klevu\TestFixtures\Customer\CustomerTrait;
use Klevu\TestFixtures\Traits\ObjectInstantiationTrait;
use Klevu\TestFixtures\Traits\TestImplementsInterfaceTrait;
use Klevu\TestFixtures\Traits\TestInterfacePreferenceTrait;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TddWizard\Fixtures\Customer\CustomerFixturePool;

/**
 * @covers \Klevu\Frontend\Service\Provider\Customer\CustomerDataProvider
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
     * @var MockObject|SessionManagerInterface|null
     */
    private SessionManagerInterface|MockObject|null $mockSessionManager = null;
    /**
     * @var string[]|false|null
     */
    private array|false|null $keys = null;

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

    public function testGet_ReturnsData_WhenNotLoggedIn(): void
    {
        $sessionId = '20945024957';

        $this->mockSessionManager->expects($this->once())
            ->method('getSessionId')
            ->willReturn($sessionId);
        $customerSessionProvider = $this->objectManager->create(
            type: CustomerSessionProviderInterface::class,
            arguments: [
                'sessionManager' => $this->mockSessionManager,
            ],
        );
        /** @var CustomerDataProviderInterface $provider */
        $provider = $this->instantiateTestObject([
            'customerSessionProvider' => $customerSessionProvider,
        ]);
        $customerData = $provider->get();
        $expectedHash = hash_hmac(
            algo: self::HASH_SHA_256,
            data: $sessionId,
            key: $this->keys[count($this->keys) - 1],
        );

        $this->assertSame(
            expected: $expectedHash,
            actual: $customerData->getSessionId(),
        );
        $this->assertNull(actual: $customerData->getCustomerGroupId());
        $this->assertNull(actual: $customerData->getIdCode());
    }

    public function testGet_ReturnsData_WhenLoggedIn(): void
    {
        $this->createCustomer();
        $customer = $this->customerFixturePool->get('test_customer');

        $sessionId = '20945024957';

        $this->mockSessionManager->expects($this->once())
            ->method('getSessionId')
            ->willReturn($sessionId);
        $customerSessionProvider = $this->objectManager->create(
            type: CustomerSessionProviderInterface::class,
            arguments: [
                'sessionManager' => $this->mockSessionManager,
            ],
        );
        $mockSessionManager = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockSessionManager->expects($this->once())
            ->method('getCustomerId')
            ->willReturn($customer->getId());

        /** @var CustomerDataProviderInterface $provider */
        $provider = $this->instantiateTestObject([
            'customerSession' => $mockSessionManager,
            'customerSessionProvider' => $customerSessionProvider,
        ]);
        $customerData = $provider->get();
        $expectedSessionIdHash = hash_hmac(
            algo: self::HASH_SHA_256,
            data: $sessionId,
            key: $this->keys[count($this->keys) - 1],
        );

        $this->assertSame(
            expected: $expectedSessionIdHash,
            actual: $customerData->getSessionId(),
        );
        $this->assertSame(
            expected: 1,
            actual: $customerData->getCustomerGroupId(),
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
        );
    }
}
