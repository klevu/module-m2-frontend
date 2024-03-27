<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

class TypeColumn extends Select
{
    /**
     * @var OptionSourceInterface
     */
    private readonly OptionSourceInterface $optionSource;

    /**
     * @param Context $context
     * @param OptionSourceInterface $optionSource
     * @param mixed[] $data
     */
    public function __construct(
        Context $context,
        OptionSourceInterface $optionSource,
        array $data = [],
    ) {
        parent::__construct($context, $data);

        $this->optionSource = $optionSource;
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     *
     * @return TypeColumn
     */
    public function setInputName(string $value): TypeColumn
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param string $value
     *
     * @return TypeColumn
     */
    public function setInputId(string $value): TypeColumn
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions(
                options: $this->optionSource->toOptionArray(),
            );
        }

        return parent::_toHtml();
    }
}
