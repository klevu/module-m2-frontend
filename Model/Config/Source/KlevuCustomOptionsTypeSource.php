<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class KlevuCustomOptionsTypeSource implements OptionSourceInterface
{
    public const TYPE_VALUE_BOOLEAN = '1';
    public const TYPE_VALUE_INTEGER = '2';
    public const TYPE_VALUE_STRING = '3';
    private const OPTIONS = [
        self::TYPE_VALUE_BOOLEAN => 'Boolean',
        self::TYPE_VALUE_INTEGER => 'Integer',
        self::TYPE_VALUE_STRING => 'String',
    ];

    /**
     * @return string[][]
     */
    public function toOptionArray(): array
    {
        $return = [];
        foreach (self::OPTIONS as $value => $label) {
            $return[] = [
                'label' => __($label),
                'value' => (string)$value,
            ];
        }

        return $return;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    public static function getLabel(string $value): ?string // phpcs:ignore Magento2.Functions.StaticFunction.StaticFunction, Generic.Files.LineLength.TooLong
    {
        return isset(self::OPTIONS[$value])
            ? __('' . self::OPTIONS[$value])->render()
            : null;
    }
}
