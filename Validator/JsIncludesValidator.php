<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Validator;

use Klevu\Frontend\ViewModel\Html\Head\JsIncludes;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\FrontendApi\Validator\ValidatorInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Validator\AbstractValidator;

class JsIncludesValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @var SerializerInterface
     */
    private readonly SerializerInterface $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        $this->_clearMessages();

        return $this->validateType($value)
            && $this->validateResourcePath($value)
            && $this->validateResourceProvider($value);
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    private function validateType(mixed $value): bool
    {
        if (is_array($value)) {
            return true;
        }
        $this->_addMessages([
            __(
                'Expected array, received %1 (%2).',
                get_debug_type($value),
                $this->serializer->serialize($value),
            )->render(),
        ]);

        return false;
    }

    /**
     * @param mixed[] $value
     *
     * @return bool
     */
    private function validateResourcePath(array $value): bool
    {
        $resourcePath = $value[JsIncludes::RESOURCE_PATH] ?? null;
        if (
            is_string($resourcePath)
            && trim($resourcePath)
        ) {
            return true;
        }
        $this->_addMessages([
            __(
                'Link %1 must be a none empty string. Received %2 (%3).',
                JsIncludes::RESOURCE_PATH,
                get_debug_type($resourcePath),
                $this->serializer->serialize($value),
            )->render(),
        ]);

        return false;
    }

    /**
     * @param mixed[] $value
     *
     * @return bool
     */
    private function validateResourceProvider(array $value): bool
    {
        if (str_starts_with(trim($value[JsIncludes::RESOURCE_PATH]), 'https://')) {
            return true;
        }
        if (null === ($value[JsIncludes::RESOURCE_PROVIDER] ?? null)) {
            $this->_addMessages([
                __(
                    'Either Link %1 must begin with "%2" or %3 must be set. Received %4.',
                    JsIncludes::RESOURCE_PATH,
                    'https://',
                    JsIncludes::RESOURCE_PROVIDER,
                    $this->serializer->serialize($value),
                )->render(),
            ]);

            return false;
        }

        return $this->validateResourceProviderType($value);
    }

    /**
     * @param mixed[] $value
     *
     * @return bool
     */
    private function validateResourceProviderType(array $value): bool
    {
        $resourceProvider = $value[JsIncludes::RESOURCE_PROVIDER] ?? null;
        if (
            ($resourceProvider instanceof SettingsProviderInterface)
            || (is_string($resourceProvider) && trim($resourceProvider))
        ) {
            return true;
        }
        $this->_addMessages([
            __(
                'Either Link %1 must be string or instance of %2. Received %3 (%4).',
                JsIncludes::RESOURCE_PROVIDER,
                SettingsProviderInterface::class,
                get_debug_type($resourceProvider),
                $this->serializer->serialize($value),
            )->render(),
        ]);

        return false;
    }
}
