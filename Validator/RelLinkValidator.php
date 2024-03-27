<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Validator;

use Klevu\FrontendApi\Service\Provider\Layout\RelLinkProviderInterface;
use Klevu\FrontendApi\Service\Provider\SettingsProviderInterface;
use Klevu\FrontendApi\Validator\ValidatorInterface;
use Magento\Framework\Validator\AbstractValidator;

class RelLinkValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function isValid(mixed $value): bool
    {
        $this->_clearMessages();

        return $this->validateType($value)
            && $this->validateLinkType($value)
            && $this->validateLinkPath($value);
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
                'Invalid Data provided for rel link configuration. Expected array, received %1.',
                get_debug_type($value),
            )->render(),
        ]);

        return false;
    }

    /**
     * @param mixed[] $value
     *
     * @return bool
     */
    private function validateLinkType(array $value): bool
    {
        $resourceType = $value[RelLinkProviderInterface::RESOURCE_TYPE] ?? null;
        if (is_string($resourceType)) {
            return true;
        }
        $this->_addMessages([
            __(
                'Invalid Resource Type provided for rel link. Expected string, received %1.',
                get_debug_type($resourceType),
            )->render(),
        ]);

        return false;
    }

    /**
     * @param mixed[] $value
     *
     * @return bool
     */
    private function validateLinkPath(array $value): bool
    {
        $linkPath = $value[RelLinkProviderInterface::RESOURCE_PATH] ?? null;
        if (
            is_string($linkPath)
            || ($linkPath instanceof SettingsProviderInterface)
        ) {
            return true;
        }
        $this->_addMessages([
            __(
                'Invalid Resource Path provided for rel link. ' .
                'Expected string or instance of SettingsProviderInterface, received %1.',
                get_debug_type($linkPath),
            )->render(),
        ]);

        return false;
    }
}
