<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Action;

use Klevu\FrontendApi\Service\Action\SetCookieActionInterface;
use Klevu\FrontendApi\Service\Provider\Cookie\CookieDurationProviderInterface;
use Klevu\FrontendApi\Service\Provider\Cookie\CookiePathProviderInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;

class SetCookieAction implements SetCookieActionInterface
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
     * @var CookieMetadataFactory
     */
    private readonly CookieMetadataFactory $cookieMetadataFactory;
    /**
     * @var CookiePathProviderInterface
     */
    private readonly CookiePathProviderInterface $cookiePathProvider;
    /**
     * @var CookieDurationProviderInterface
     */
    private readonly CookieDurationProviderInterface $cookieDurationProvider;
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param SerializerInterface $serializer
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param CookiePathProviderInterface $cookiePathProvider
     * @param CookieDurationProviderInterface $cookieDurationProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        SerializerInterface $serializer,
        CookieMetadataFactory $cookieMetadataFactory,
        CookiePathProviderInterface $cookiePathProvider,
        CookieDurationProviderInterface $cookieDurationProvider,
        LoggerInterface $logger,
    ) {
        $this->cookieManager = $cookieManager;
        $this->serializer = $serializer;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookiePathProvider = $cookiePathProvider;
        $this->cookieDurationProvider = $cookieDurationProvider;
        $this->logger = $logger;
    }

    /**
     * @param string $name
     * @param mixed[] $data
     *
     * @return void
     */
    public function execute(string $name, array $data): void
    {
        try {
            $this->cookieManager->setPublicCookie(
                name: $name,
                value: $this->serializer->serialize($data),
                metadata: $this->generateMetaData(),
            );
        } catch (\Exception $exception) {
            $this->logger->error(
                message: 'Method: {method}, Error {error}',
                context: [
                    'method' => __METHOD__,
                    'error' => $exception->getMessage(),
                    'exception' => $exception,
                ],
            );
        }
    }

    /**
     * @return PublicCookieMetadata
     */
    private function generateMetaData(): PublicCookieMetadata
    {
        $cookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $cookieMetadata->setDuration(
            duration: $this->cookieDurationProvider->get(),
        );
        $cookieMetadata->setPath(
            path: $this->cookiePathProvider->get(),
        );
        $cookieMetadata->setHttpOnly(false);

        return $cookieMetadata;
    }
}
