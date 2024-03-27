<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\Frontend\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

class CustomSettings extends AbstractFieldArray
{
    public const COLUMN_NAME_PATH = 'path';
    public const COLUMN_NAME_TYPE = 'type';
    public const COLUMN_NAME_VALUE = 'value';

    /**
     * @var BlockInterface|TypeColumn|null
     */
    private TypeColumn|BlockInterface|null $typeRenderer = null;

    /**
     * Prepare rendering the new field by adding all the needed columns
     * @throws LocalizedException
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn(
            name: static::COLUMN_NAME_PATH,
            params: [
                'label' => __('Path'),
                'class' => 'required-entry',
                'validate' => 'integer',
            ],
        );
        $this->addColumn(
            name: static::COLUMN_NAME_TYPE,
            params: [
                'label' => __('Type'),
                'renderer' => $this->getTypeRenderer(),
                'class' => 'required-entry',
            ],
        );
        $this->addColumn(
            name: static::COLUMN_NAME_VALUE,
            params: [
                'label' => __('Value'),
                'class' => 'required-entry',
            ],
        );

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add')->render();
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     *
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $type = $row->getType();
        if ($type !== null) {
            // annotation required for PHPStan
            /** @var TypeColumn $typeRenderer */
            $typeRenderer = $this->getTypeRenderer();
            $options['option_' . $typeRenderer->calcOptionHash($type)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return BlockInterface|TypeColumn
     * @throws LocalizedException
     */
    private function getTypeRenderer(): BlockInterface|TypeColumn
    {
        if (!$this->typeRenderer) {
            $layout = $this->getLayout();
            $this->typeRenderer = $layout->createBlock(
                type: TypeColumn::class,
                name: '',
                arguments: ['data' => ['is_render_to_js_template' => true]],
            );
        }

        return $this->typeRenderer;
    }
}
