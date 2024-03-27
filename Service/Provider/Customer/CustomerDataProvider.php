<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Customer;

use Klevu\Customer\Service\Provider\CustomerIdProviderInterface;
use Klevu\Customer\Service\Provider\CustomerSessionProviderInterface;
use Klevu\FrontendApi\Api\Data\CustomerDataInterface;
use Klevu\FrontendApi\Api\Data\CustomerDataInterfaceFactory;
use Klevu\FrontendApi\Service\Provider\Customer\CustomerDataProviderInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;

class CustomerDataProvider implements CustomerDataProviderInterface
{
    /**
     * @var CustomerDataInterfaceFactory
     */
    private readonly CustomerDataInterfaceFactory $customerDataFactory;
    /**
     * @var SessionManagerInterface
     */
    private readonly SessionManagerInterface $customerSession;
    /**
     * @var CustomerRepositoryInterface
     */
    private readonly CustomerRepositoryInterface $customerRepository;
    /**
     * @var RemoteAddress
     */
    private readonly RemoteAddress $remoteAddress;
    /**
     * @var CustomerIdProviderInterface
     */
    private readonly CustomerIdProviderInterface $customerIdProvider;
    /**
     * @var CustomerSessionProviderInterface
     */
    private readonly CustomerSessionProviderInterface $customerSessionProvider;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;
    /**
     * @var CustomerInterface|null
     */
    private ?CustomerInterface $customer = null;

    /**
     * @param CustomerDataInterfaceFactory $customerDataFactory
     * @param SessionManagerInterface $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param RemoteAddress $remoteAddress
     * @param CustomerIdProviderInterface $customerIdProvider
     * @param CustomerSessionProviderInterface $customerSessionProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        CustomerDataInterfaceFactory $customerDataFactory,
        SessionManagerInterface $customerSession,
        CustomerRepositoryInterface $customerRepository,
        RemoteAddress $remoteAddress,
        CustomerIdProviderInterface $customerIdProvider,
        CustomerSessionProviderInterface $customerSessionProvider,
        LoggerInterface $logger,
    ) {
        $this->customerDataFactory = $customerDataFactory;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        $this->remoteAddress = $remoteAddress;
        $this->customerIdProvider = $customerIdProvider;
        $this->customerSessionProvider = $customerSessionProvider;
        $this->logger = $logger;
    }

    /**
     * @return CustomerDataInterface
     */
    public function get(): CustomerDataInterface
    {
        $customerData = $this->createCustomerData();
        $customerData->setSessionId(
            sessionId: $this->customerSessionProvider->get(),
        );
        $shopperIp = $this->remoteAddress->getRemoteAddress(ipToLong: false);
        if ($shopperIp) {
            $customerData->setShopperIp(shopperIp: $shopperIp);
        }
        $customer = $this->getCustomer();
        if ($customer) {
            $customerData->setCustomerGroupId(
                customerGroupId: (int)$customer->getGroupId(),
            );
            $customerData->setIdCode(
                idCode: $this->generateIdCode(customer: $customer),
            );
        }

        return $customerData;
    }

    /**
     * @return CustomerDataInterface
     */
    private function createCustomerData(): CustomerDataInterface
    {
        return $this->customerDataFactory->create();
    }

    /**
     * @return CustomerInterface|null
     */
    private function getCustomer(): ?CustomerInterface
    {
        if (
            null !== $this->customer
            || !method_exists($this->customerSession, 'getCustomerId')
        ) {
            return $this->customer;
        }
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            return null;
        }
        try {
            $this->customer = $this->customerRepository->getById((int)$customerId);
        } catch (NoSuchEntityException) { //phpcs:ignore Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
            // No action required
        } catch (\Exception $exception) {
            $this->logger->error(
                message: 'Method: {method}, Error: {error}',
                context: [
                    'method' => __METHOD__,
                    'error' => $exception->getMessage(),
                    'customerId' => $customerId,
                ],
            );
        }

        return $this->customer;
    }

    /**
     * @param CustomerInterface $customer
     *
     * @return string
     */
    private function generateIdCode(CustomerInterface $customer): string
    {
        return $this->customerIdProvider->get(
            email: $customer->getEmail(),
        );
    }
}
