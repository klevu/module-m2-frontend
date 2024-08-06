<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Test\Api;

use Klevu\TestFixtures\Customer\CustomerTrait;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\WebapiAbstract;
use TddWizard\Fixtures\Customer\CustomerFixturePool;

/**
 * @covers \Klevu\Frontend\WebApi\CustomerDataProvider
 */
class CustomerDataApiTest extends WebapiAbstract
{
    use CustomerTrait;

    private const RESOURCE_PATH = '/V1/klevu/customerData?XDEBUG_SESSION_START=PHPSTORM';

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

        $this->objectManager = Bootstrap::getObjectManager();
        $this->customerFixturePool = $this->objectManager->get(CustomerFixturePool::class);
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
     * @dataProvider testErrorReturned_WhenIncorrectHttpMethod_DataProvider
     */
    public function testErrorReturned_WhenIncorrectHttpMethod(string $method): void
    {
        $this->_markTestAsRestOnly();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('{"message":"Request does not match any route.","trace":null}');

        $serviceInfo = [
            WebapiAbstract::ADAPTER_REST => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => $method,
            ],
        ];
        $this->_webApiCall(serviceInfo: $serviceInfo);
    }

    /**
     * @return string[][]
     */
    public function testErrorReturned_WhenIncorrectHttpMethod_DataProvider(): array
    {
        return [
            [RestRequest::HTTP_METHOD_POST],
            [RestRequest::HTTP_METHOD_PUT],
            [RestRequest::HTTP_METHOD_DELETE],
        ];
    }

    public function testRestApiCall_ReturnsCustomerData_NotLoggedIn(): void
    {
        $this->_markTestAsRestOnly();

        $serviceInfo = [
            WebapiAbstract::ADAPTER_REST => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => RestRequest::HTTP_METHOD_GET,
                'token' => '',
            ],
        ];
        $requestData = [];
        $response = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertIsArray(actual: $response, message: 'Response');
        $this->assertArrayHasKey(key: 'session_id', array: $response);
        $this->assertIsString(actual: $response['session_id']);
        $this->assertArrayHasKey(key: 'shopper_ip', array: $response);
        $this->assertIsString(actual: $response['shopper_ip']);
        $this->assertArrayHasKey(key: 'revalidate_after', array: $response);
        $this->assertIsInt(actual: $response['revalidate_after']);
    }

    /**
     * @magentoAppArea frontend
     */
    public function testRestApiCall_ReturnsCustomerData_LoggedIn(): void
    {
        // @TODO fix this test
        $this->markTestIncomplete(
            'Functional test Api call is black boxed.'
            . 'Customer session is not maintained from the test setup.'
            . 'Fix is taking too long. Will come back to this.',
        );
        $this->_markTestAsRestOnly(); // @phpstan-ignore-line Remove ignore when test no longer imcomplete
        $this->createCustomer([
            'email' => 'customer.name@domain.url',
        ]);
        $customerFixture = $this->customerFixturePool->get('test_customer');
        $customerData = $customerFixture->getCustomer();
        $customerFactory = $this->objectManager->get(CustomerFactory::class);
        $customer = $customerFactory->create(['data' => $customerData->__toArray()]);
        $session = $this->objectManager->get(CustomerSession::class);
        $session->setCustomerAsLoggedIn($customer);
        $session->setCustomerData($customerData);
        $session->setCustomerId($customerFixture->getId());
        $this->objectManager->addSharedInstance(
            instance: $session,
            className: CustomerSession::class,
            forPreference: true,
        );

        $serviceInfo = [
            WebapiAbstract::ADAPTER_REST => [
                'resourcePath' => self::RESOURCE_PATH,
                'httpMethod' => RestRequest::HTTP_METHOD_GET,
                'token' => '',
            ],
        ];
        $requestData = [];
        $response = $this->_webApiCall($serviceInfo, $requestData);

        $this->assertIsArray(actual: $response, message: 'Response');
        $this->assertArrayHasKey(key: 'session_id', array: $response);
        $this->assertIsString(actual: $response['session_id']);
        $this->assertArrayHasKey(key: 'shopper_ip', array: $response);
        $this->assertIsString(actual: $response['shopper_ip']);
        $this->assertArrayHasKey(key: 'revalidate_after', array: $response);
        $this->assertIsInt(actual: $response['revalidate_after']);
        $this->assertArrayHasKey(key: 'id_code', array: $response);
        $this->assertIsString(actual: $response['id_code']);
        $this->assertArrayHasKey(key: 'customer_group_id', array: $response);
        $this->assertIsInt(actual: $response['customer_group_id']);
    }
}
