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
 * Grid [[@doctodo class_description:infinite\web\grid\Grid]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Grid extends \infinite\base\Object
{
    //public $fillPreviousRows = true;
    /**
     * @var [[@doctodo var_type:rowClass]] [[@doctodo var_description:rowClass]]
     */
    public $rowClass = 'infinite\web\grid\Row';

    /**
     * @var [[@doctodo var_type:baseRow]] [[@doctodo var_description:baseRow]]
     */
    public $baseRow = [];
    /**
     * @var [[@doctodo var_type:htmlOptions]] [[@doctodo var_description:htmlOptions]]
     */
    public $htmlOptions = ['class' => 'infinite-grid'];

    /**
     * @var [[@doctodo var_type:_id]] [[@doctodo var_description:_id]]
     */
    protected $_id;
    /**
     * @var [[@doctodo var_type:_prepended]] [[@doctodo var_description:_prepended]]
     */
    protected $_prepended = [];
    /**
     * @var [[@doctodo var_type:_appended]] [[@doctodo var_description:_appended]]
     */
    protected $_appended = [];
    /**
     * @var [[@doctodo var_type:_rows]] [[@doctodo var_description:_rows]]
     */
    protected $_rows = [];
    /**
     * @var [[@doctodo var_type:_currentRow]] [[@doctodo var_description:_currentRow]]
     */
    protected $_currentRow;

    /**
     * [[@doctodo method_description:output]].
     */
    public function output()
    {
        echo $this->generate();
    }

    /**
     * [[@doctodo method_description:generate]].
     *
     * @return [[@doctodo return_type:generate]] [[@doctodo return_description:generate]]
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
     * [[@doctodo method_description:prepend]].
     *
     * @param [[@doctodo param_type:item]] $item [[@doctodo param_description:item]]
     */
    public function prepend($item)
    {
        $this->_prepended[] = $item;
    }

    /**
     * [[@doctodo method_description:append]].
     *
     * @param [[@doctodo param_type:item]] $item [[@doctodo param_description:item]]
     */
    public function append($item)
    {
        $this->_appended[] = $item;
    }

    /**
     * [[@doctodo method_description:addRow]].
     *
     * @param [[@doctodo param_type:item]] $item [[@doctodo param_description:item]]
     *
     * @return [[@doctodo return_type:addRow]] [[@doctodo return_description:addRow]]
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
     * [[@doctodo method_description:addRows]].
     *
     * @param [[@doctodo param_type:items]] $items [[@doctodo param_description:items]]
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
     *
     * @param [[@doctodo param_type:items]] $items [[@doctodo param_description:items]]
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
     *
     * @return [[@doctodo return_type:getCurrentRow]] [[@doctodo return_description:getCurrentRow]]
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
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        if (is_null($this->_id)) {
            $this->_id = md5(microtime() . mt_rand());
        }

        return $this->_id;
    }
}
