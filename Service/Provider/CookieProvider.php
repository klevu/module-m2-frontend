<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider;

use Klevu\FrontendApi\Service\Provider\CookieProviderInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;

class CookieProvider implements CookieProviderInterface
{
    /**
     * @var CookieManagerInterface
     */
    private readonly CookieManagerInterface $cookieManager;
    /**
     * @var SerializerInterface
     */
    private readonly SerializerInterface $serializer;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param SerializerInterface $serializer
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        SerializerInterface $serializer,
    ) {
        $this->cookieManager = $cookieManager;
        $this->serializer = $serializer;
    }

    /**
     * @param string $name
     *
     * @return mixed[]
     */
    public function get(string $name): array
    {
        $cookie = $this->cookieManager->getCookie($name);
        if (null === $cookie) {
            return [];
        }
        try {
            $cookieData = $this->serializer->unserialize($cookie);
        } catch (\InvalidArgumentException) {
            $cookieData = [];
        }
        if (!is_array($cookieData)) {
            $cookieData = [];
        }

        return $cookieData;
    }
}
