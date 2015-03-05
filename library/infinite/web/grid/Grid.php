<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\grid;

use infinite\helpers\Html;
use Yii;

/**
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Grid extends \infinite\base\Object
{
    //public $fillPreviousRows = true;
    /**
     */
    public $rowClass = 'infinite\web\grid\Row';

    public $baseRow = [];
    public $htmlOptions = ['class' => 'infinite-grid'];

    protected $_id;
    /**
     */
    protected $_prepended = [];
    /**
     */
    protected $_appended = [];
    /**
     */
    protected $_rows = [];
    /**
     */
    protected $_currentRow;

    /**
     */
    public function output()
    {
        echo $this->generate();
    }

    /**
     *
     */
    public function generate()
    {
        $items = [];
        $this->htmlOptions['id'] = $this->id;
        $items[] = Html::beginTag('div', $this->htmlOptions);
        foreach ($this->_prepended as $item) {
            $items[] = $item->generate();
        }
        foreach ($this->_rows as $row) {
            $items[] = $row->generate();
        }
        foreach ($this->_appended as $item) {
            $items[] = $item->generate();
        }
        $items[] = Html::endTag('div');

        return implode('', $items);
    }

    /**
     *
     */
    public function prepend($item)
    {
        $this->_prepended[] = $item;
    }

    /**
     *
     */
    public function append($item)
    {
        $this->_appended[] = $item;
    }

    /**
     *
     */
    public function addRow($item)
    {
        if (is_array($item)) {
            $item = array_merge($this->baseRow, ['class' => $this->rowClass, 'cells' => $item]);
            $item = Yii::createObject($item);
        }
        $this->_rows[] = $item;
        $this->_currentRow = null;

        return $item;
    }

    /**
     *
     */
    public function addRows($items)
    {
        foreach ($items as $item) {
            $this->_rows[] = $this->addRow($item);
        }
        $this->_currentRow = null;
    }

    /**
     * Set cells.
     */
    public function setCells($items)
    {
        Yii::beginProfile(__CLASS__ . ':' . __FUNCTION__);
        while (!empty($items)) {
            $this->currentRow->addCells($items);
            if (!empty($items)) {
                $this->_currentRow = null;
            }
        }
        Yii::endProfile(__CLASS__ . ':' . __FUNCTION__);
    }

    /**
     * Get current row.
     */
    public function getCurrentRow()
    {
        if (isset($this->_currentRow) && $this->_currentRow->isFilled()) {
            $this->_currentRow = null;
        }
        if (is_null($this->_currentRow)) {
            $this->_currentRow = Yii::createObject(array_merge(['class' => $this->rowClass], $this->baseRow));
            $this->_rows[] = $this->_currentRow;
        }

        return $this->_currentRow;
    }

    /**
     * Get id.
     */
    public function getId()
    {
        if (is_null($this->_id)) {
            $this->_id = md5(microtime() . mt_rand());
        }

        return $this->_id;
    }
}
