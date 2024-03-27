<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Model\Customer;

use Klevu\FrontendApi\Api\Data\CustomerDataInterface;

class CustomerData implements CustomerDataInterface
{
    private const REVALIDATE_AFTER_SECONDS = 1800;

    /**
     * @var int|null
     */
    private ?int $customerGroupId = null;
    /**
     * @var string|null
     */
    private ?string $idCode = null;
    /**
     * @var int|null
     */
    private ?int $revalidateAfter = null;
    /**
     * @var string
     */
    private string $sessionId = '';
    /**
     * @var string
     */
    private string $shopperIp = '';

    /**
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     *
     * @return void
     */
    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return int|null
     */
    public function getCustomerGroupId(): ?int
    {
        return $this->customerGroupId;
    }

    /**
     * @param int $customerGroupId
     *
     * @return void
     */
    public function setCustomerGroupId(int $customerGroupId): void
    {
        $this->customerGroupId = $customerGroupId;
    }

    /**
     * @return string|null
     */
    public function getIdCode(): ?string
    {
        return $this->idCode;
    }

    /**
     * @param string $idCode
     *
     * @return void
     */
    public function setIdCode(string $idCode): void
    {
        $this->idCode = $idCode;
    }

    /**
     * @return string
     */
    public function getShopperIp(): string
    {
        return $this->shopperIp;
    }

    /**
     * @param string $shopperIp
     *
     * @return void
     */
    public function setShopperIp(string $shopperIp): void
    {
        $this->shopperIp = $shopperIp;
    }

    /**
     * @return int
     */
    public function getRevalidateAfter(): int
    {
        if (null === $this->revalidateAfter) {
            $this->setRevalidateAfter(time() + self::REVALIDATE_AFTER_SECONDS);
        }

        return $this->revalidateAfter;
    }

    /**
     * @param int $revalidateAfter
     *
     * @return void
     */
    public function setRevalidateAfter(int $revalidateAfter): void
    {
        $this->revalidateAfter = $revalidateAfter;
    }
}
