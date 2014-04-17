<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\grid;

use Yii;

/**
 * Grid [@doctodo write class description for Grid]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Grid extends \infinite\base\Object
{
    //public $fillPreviousRows = true;
    /**
     * @var __var_rowClass_type__ __var_rowClass_description__
     */
    public $rowClass = 'infinite\web\grid\Row';

    /**
     * @var __var__prepended_type__ __var__prepended_description__
     */
    protected $_prepended = [];
    /**
     * @var __var__appended_type__ __var__appended_description__
     */
    protected $_appended = [];
    /**
     * @var __var__rows_type__ __var__rows_description__
     */
    protected $_rows = [];
    /**
     * @var __var__currentRow_type__ __var__currentRow_description__
     */
    protected $_currentRow;

    /**
     * __method_output_description__
     */
    public function output()
    {
        echo $this->generate();
    }

    /**
     * __method_generate_description__
     * @return __return_generate_type__ __return_generate_description__
     */
    public function generate()
    {
        $items = [];
        foreach ($this->_prepended as $item) {
            $items[] = $item->generate();
        }
        foreach ($this->_rows as $row) {
            $items[] = $row->generate();
        }
        foreach ($this->_appended as $item) {
            $items[] = $item->generate();
        }

        return implode('', $items);
    }

    /**
     * __method_prepend_description__
     * @param __param_item_type__ $item __param_item_description__
     */
    public function prepend($item)
    {
        $this->_prepended[] = $item;
    }

    /**
     * __method_append_description__
     * @param __param_item_type__ $item __param_item_description__
     */
    public function append($item)
    {
        $this->_appended[] = $item;
    }

    /**
     * __method_addRow_description__
     * @param __param_item_type__ $item __param_item_description__
     * @return __return_addRow_type__ __return_addRow_description__
     */
    public function addRow($item)
    {
        if (is_array($item)) {
            $item = Yii::createObject(['class' => $this->rowClass, 'cells' => $item]);;
        }
        $this->_rows[] = $item;
        $this->_currentRow = null;

        return $item;
    }

    /**
     * __method_addRows_description__
     * @param __param_items_type__ $items __param_items_description__
     */
    public function addRows($items)
    {
        foreach ($items as $item) {
            $this->_rows[] = $this->addRow($item);
        }
        $this->_currentRow = null;
    }

    /**
     * Set cells
     * @param __param_items_type__ $items __param_items_description__
     */
    public function setCells($items)
    {
        Yii::beginProfile(__CLASS__ . ':'. __FUNCTION__);
        while (!empty($items)) {
            $this->currentRow->addCells($items);
            if (!empty($items)) {
                $this->_currentRow = null;
            }
        }
        Yii::endProfile(__CLASS__ . ':'. __FUNCTION__);
    }

    /**
     * Get current row
     * @return __return_getCurrentRow_type__ __return_getCurrentRow_description__
     */
    public function getCurrentRow()
    {
        if (isset($this->_currentRow) && $this->_currentRow->isFilled()) {
            $this->_currentRow = null;
        }
        if (is_null($this->_currentRow)) {
            $this->_currentRow = Yii::createObject(['class' => $this->rowClass]);
            $this->_rows[] = $this->_currentRow;
        }

        return $this->_currentRow;
    }
}
