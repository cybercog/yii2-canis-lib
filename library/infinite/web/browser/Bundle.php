<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

use infinite\base\exceptions\Exception;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Bundle [[@doctodo class_description:infinite\web\browser\Bundle]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Bundle extends \infinite\base\Object
{
    /**
     * @var [[@doctodo var_type:itemClass]] [[@doctodo var_description:itemClass]]
     */
    public $itemClass = 'infinite\web\browser\Item';
    /**
     * @var [[@doctodo var_type:limit]] [[@doctodo var_description:limit]]
     */
    public $limit = 30;
    /**
     * @var [[@doctodo var_type:_id]] [[@doctodo var_description:_id]]
     */
    protected $_id; // never set, based on instructions
    /**
     * @var [[@doctodo var_type:_instructions]] [[@doctodo var_description:_instructions]]
     */
    protected $_instructions;
    /**
     * @var [[@doctodo var_type:_filterQuery]] [[@doctodo var_description:_filterQuery]]
     */
    protected $_filterQuery = false;
    /**
     * @var [[@doctodo var_type:_type]] [[@doctodo var_description:_type]]
     */
    protected $_type; // pivot: category list; item: items list
    /**
     * @var [[@doctodo var_type:_typeOptions]] [[@doctodo var_description:_typeOptions]]
     */
    protected $_typeOptions = [];
    /**
     * @var [[@doctodo var_type:_items]] [[@doctodo var_description:_items]]
     */
    protected $_items = [];
    /**
     * @var [[@doctodo var_type:_handled]] [[@doctodo var_description:_handled]]
     */
    protected $_handled = false;
    /**
     * @var [[@doctodo var_type:_total]] [[@doctodo var_description:_total]]
     */
    protected $_total;
    /**
     * @var [[@doctodo var_type:_offset]] [[@doctodo var_description:_offset]]
     */
    protected $_offset = 0;
    /**
     * @var [[@doctodo var_type:_baseInstructions]] [[@doctodo var_description:_baseInstructions]]
     */
    protected $_baseInstructions = ['task' => null];

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        if (is_null($this->_id)) {
            $this->_id = md5(serialize($this->instructions));
        }

        return $this->_id;
    }

    /**
     * Get instructions.
     *
     * @throws InvalidConfigException [[@doctodo exception_description:InvalidConfigException]]
     * @return [[@doctodo return_type:getInstructions]] [[@doctodo return_description:getInstructions]]
     *
     */
    public function getInstructions()
    {
        if (is_null($this->_instructions)) {
            throw new InvalidConfigException('Browser response bundle requires instructions');
        }

        return $this->_instructions;
    }

    /**
     * Set instructions.
     *
     * @param [[@doctodo param_type:instructions]] $instructions [[@doctodo param_description:instructions]]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     */
    public function setInstructions($instructions)
    {
        if (!is_null($this->_instructions)) {
            throw new Exception('Instructions for browser responses can only be set once.');
        }
        $this->_instructions = array_merge($this->_baseInstructions, $instructions);
        if (isset($this->_instructions['id'])) {
            $this->_id = $this->_instructions['id'];
        }
        if (isset($this->_instructions['offset'])) {
            $this->_offset = $this->_instructions['offset'];
        }

        if (isset($this->_instructions['filterQuery'])) {
            $this->filterQuery = $this->_instructions['filterQuery'];
        }

        unset($this->_instructions['id'], $this->_instructions['offset'], $this->_instructions['filterQuery']);
    }

    /**
     * [[@doctodo method_description:addItem]].
     *
     * @param [[@doctodo param_type:item]] $item [[@doctodo param_description:item]]
     *
     * @return [[@doctodo return_type:addItem]] [[@doctodo return_description:addItem]]
     */
    public function addItem($item)
    {
        if (!isset($this->_items)) {
            $this->_items = [];
        }
        if (!is_array($item)) {
            $item = [];
        }
        if (!isset($item['class'])) {
            $item['class'] = $this->itemClass;
        }
        $item = Yii::createObject($item);
        $this->_items[$item->id] = $item;

        return $item;
    }

    /**
     * [[@doctodo method_description:package]].
     *
     * @return [[@doctodo return_type:package]] [[@doctodo return_description:package]]
     */
    public function package()
    {
        $package = [];
        $package['id'] = $this->id;
        $package['instructions'] = $this->instructions;
        $package['filterQuery'] = $this->filterQuery;
        $package['type'] = $this->type;
        $package['typeOptions'] = $this->typeOptions;
        $package['total'] = $this->total;
        $package['bundle'] = false;
        $package['bundle'] = ['offset' => $this->offset, 'size' => count($this->_items), 'items' => []];
        foreach ($this->_items as $item) {
            $package['bundle']['items'][$item->id] = $item->package();
        }

        return $package;
    }

    /**
     * Set filter query.
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setFilterQuery($value)
    {
        if ($value === 'false') {
            $value = false;
        }
        $this->_filterQuery = $value;
    }

    /**
     * Get filter query.
     *
     * @return [[@doctodo return_type:getFilterQuery]] [[@doctodo return_description:getFilterQuery]]
     */
    public function getFilterQuery()
    {
        return $this->_filterQuery;
    }

    /**
     * Set type options.
     *
     * @param [[@doctodo param_type:options]] $options [[@doctodo param_description:options]]
     */
    public function setTypeOptions($options)
    {
        $this->_typeOptions = array_merge($this->_typeOptions, $options);
    }

    /**
     * Get type options.
     *
     * @return [[@doctodo return_type:getTypeOptions]] [[@doctodo return_description:getTypeOptions]]
     */
    public function getTypeOptions()
    {
        return $this->_typeOptions;
    }

    /**
     * Set type.
     *
     * @param [[@doctodo param_type:type]] $type [[@doctodo param_description:type]]
     *
     * @throws InvalidConfigException [[@doctodo exception_description:InvalidConfigException]]
     */
    public function setType($type)
    {
        $acceptableTypes = ['pivot', 'item'];
        if (!in_array($type, $acceptableTypes)) {
            throw new InvalidConfigException('Browser response bundle must be one of the following types: ' . implode(', ', $acceptableTypes));
        }
        $this->_type = $type;
    }

    /**
     * Get type.
     *
     * @return [[@doctodo return_type:getType]] [[@doctodo return_description:getType]]
     */
    public function getType()
    {
        if (is_null($this->_type)) {
            $this->type = 'item';
        }

        return $this->_type;
    }

    /**
     * [[@doctodo method_description:predictTotal]].
     *
     * @return [[@doctodo return_type:predictTotal]] [[@doctodo return_description:predictTotal]]
     */
    public function predictTotal()
    {
        if ($this->handler) {
            return $this->handler->total;
        }

        return false;
    }

    /**
     * Get total.
     *
     * @return [[@doctodo return_type:getTotal]] [[@doctodo return_description:getTotal]]
     */
    public function getTotal()
    {
        if (is_null($this->_total)) {
            if (($total = $this->predictTotal())) {
                $this->_total = $total;
            } elseif (isset($this->_items)) {
                return count($this->_items);
            } else {
                $this->_total = false;
            }
        }

        return $this->_total;
    }

    /**
     * Set total.
     *
     * @param [[@doctodo param_type:total]] $total [[@doctodo param_description:total]]
     */
    public function setTotal($total)
    {
        $this->_total = $total;
    }

    /**
     * Get offset.
     *
     * @return [[@doctodo return_type:getOffset]] [[@doctodo return_description:getOffset]]
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * Set offset.
     *
     * @param [[@doctodo param_type:offset]] $offset [[@doctodo param_description:offset]]
     */
    public function setOffset($offset)
    {
        $this->_offset = $offset;
    }

    /**
     * Get handlers.
     *
     * @return [[@doctodo return_type:getHandlers]] [[@doctodo return_description:getHandlers]]
     */
    public function getHandlers()
    {
        return [];
    }

    /**
     * Get handler.
     *
     * @return [[@doctodo return_type:getHandler]] [[@doctodo return_description:getHandler]]
     */
    public function getHandler()
    {
        $handlers = $this->handlers;
        if (isset($this->instructions['handler'])
            && isset($handlers[$this->instructions['handler']])) {
            return Yii::createObject([
                'class' => $handlers[$this->instructions['handler']],
                'bundle' => $this,
            ]);
        }

        return false;
    }

    /**
     * [[@doctodo method_description:handle]].
     *
     * @return [[@doctodo return_type:handle]] [[@doctodo return_description:handle]]
     */
    public function handle()
    {
        if ($this->_handled) {
            return true;
        }
        $handler = $this->handler;
        if (!$handler) {
            return false;
        }
        $items = $handler->items;
        if ($items === false) {
            return false;
        }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        $this->_handled = true;
    }
}
