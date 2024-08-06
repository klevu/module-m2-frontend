<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Service\Provider\Urls;

use Klevu\Configuration\Service\Provider\ScopeProviderInterface;
use Klevu\FrontendApi\Service\Provider\Urls\AddToCartUrlProviderInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class AddToCartUrlProvider implements AddToCartUrlProviderInterface
{
    private const ROUTE_NAME = 'checkout';
    private const CONTROLLER_NAME = 'cart';
    private const ACTION_NAME = 'add';

    /**
     * @var UrlInterface
     */
    private readonly UrlInterface $url;
    /**
     * @var EncoderInterface
     */
    private readonly EncoderInterface $urlEncoder;
    /**
     * @var RequestInterface
     */
    private readonly RequestInterface $request;
    /**
     * @var ScopeProviderInterface
     */
    private readonly ScopeProviderInterface $scopeProvider;

    /**
     * @param UrlInterface $url
     * @param EncoderInterface $urlEncoder
     * @param RequestInterface $request
     * @param ScopeProviderInterface $scopeProvider
     */
    public function __construct(
        UrlInterface $url,
        EncoderInterface $urlEncoder,
        RequestInterface $request,
        ScopeProviderInterface $scopeProvider,
    ) {
        $this->url = $url;
        $this->urlEncoder = $urlEncoder;
        $this->request = $request;
        $this->scopeProvider = $scopeProvider;
    }

    /**
     * @param int|null $productId
     * @param mixed[]|null $additional
     *
     * @return string
     */
    public function get(?int $productId = null, ?array $additional = []): string
    {
        $routeParams = [
            ActionInterface::PARAM_NAME_URL_ENCODED => $this->encodedUrl($additional),
            '_secure' => $this->request->isSecure(),
        ];
        unset($additional['useUencPlaceholder']);
        if (null !== $productId) {
            $routeParams['product'] = $productId;
        }
        if (!empty($additional)) {
            $routeParams = array_merge($routeParams, $additional);
        }
        $scope = $this->scopeProvider->getCurrentScope();
        if ($scope->getScopeType() === ScopeInterface::SCOPE_STORES) {
            /**
             * \Magento\Framework\Url\RouteParamsResolver::setScope has type hint of string "setScope(string $scope)"
             * yet \Magento\Store\Url\Plugin\RouteParamsResolver::beforeSetRouteParams
             * expects "$currentScope = $subject->getScope();" to be instanceof StoreInterface
             * Therefore set scope as instanceof StoreInterface here otherwise it is ignored
             */
            $routeParams['_scope'] = $scope->getScopeObject();
            $routeParams['_scope_to_url'] = true;
        }
        if ($this->isCartPage()) {
            $routeParams['in_cart'] = 1;
        }

        return $this->url->getUrl(
            routePath: self::ROUTE_NAME . '/' . self::CONTROLLER_NAME . '/' . self::ACTION_NAME,
            routeParams: $routeParams,
        );
    }

    /**
     * @return bool
     */
    private function isCartPage(): bool
    {
        if (
            !method_exists($this->request, 'getRouteName')
            || !method_exists($this->request, 'getControllerName')
        ) {
            return false;
        }

        return $this->request->getRouteName() === self::ROUTE_NAME
            && $this->request->getControllerName() === self::CONTROLLER_NAME;
    }

    /**
     * @param mixed[]|null $additional
     *
     * @return string
     */
    private function encodedUrl(?array $additional = []): string
    {
        return isset($additional['useUencPlaceholder'])
            ? "%uenc%"
            : $this->urlEncoder->encode($this->url->getCurrentUrl());
    }
}
