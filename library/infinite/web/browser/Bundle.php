<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

use Yii;
use infinite\base\exceptions\Exception;
use yii\base\InvalidConfigException;

/**
 * Bundle [@doctodo write class description for Bundle]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Bundle extends \infinite\base\Object
{
    /**
     * @var __var_itemClass_type__ __var_itemClass_description__
     */
    public $itemClass = 'infinite\\web\\browser\\Item';
    /**
     * @var __var_limit_type__ __var_limit_description__
     */
    public $limit = 30;
    /**
     * @var __var__id_type__ __var__id_description__
     */
    protected $_id; // never set, based on instructions
    /**
     * @var __var__instructions_type__ __var__instructions_description__
     */
    protected $_instructions;
    /**
     * @var __var__filterQuery_type__ __var__filterQuery_description__
     */
    protected $_filterQuery = false;
    /**
     * @var __var__type_type__ __var__type_description__
     */
    protected $_type; // pivot: category list; item: items list
    /**
     * @var __var__typeOptions_type__ __var__typeOptions_description__
     */
    protected $_typeOptions = [];
    /**
     * @var __var__items_type__ __var__items_description__
     */
    protected $_items;
    /**
     * @var __var__handled_type__ __var__handled_description__
     */
    protected $_handled = false;
    /**
     * @var __var__total_type__ __var__total_description__
     */
    protected $_total;
    /**
     * @var __var__offset_type__ __var__offset_description__
     */
    protected $_offset = 0;
    /**
     * @var __var__baseInstructions_type__ __var__baseInstructions_description__
     */
    protected $_baseInstructions = ['task' => null];

    /**
     * Get id
     * @return __return_getId_type__ __return_getId_description__
     */
    public function getId()
    {
        if (is_null($this->_id)) {
            $this->_id = md5(serialize($this->instructions));
        }

        return $this->_id;
    }

    /**
     * Get instructions
     * @return __return_getInstructions_type__ __return_getInstructions_description__
     * @throws InvalidConfigException __exception_InvalidConfigException_description__
     */
    public function getInstructions()
    {
        if (is_null($this->_instructions)) {
            throw new InvalidConfigException('Browser response bundle requires instructions');
        }

        return $this->_instructions;
    }

    /**
     * Set instructions
     * @param __param_instructions_type__ $instructions __param_instructions_description__
     * @throws Exception __exception_Exception_description__
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
     * __method_addItem_description__
     * @param __param_item_type__ $item __param_item_description__
     * @return __return_addItem_type__ __return_addItem_description__
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
     * __method_package_description__
     * @return __return_package_type__ __return_package_description__
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
        if (isset($this->_items)) {
            $package['bundle'] = ['offset' => $this->offset, 'size' => count($this->_items), 'items' => []];
            foreach ($this->_items as $item) {
                $package['bundle']['items'][$item->id] = $item->package();
            }
        }

        return $package;
    }

    /**
     * Set filter query
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setFilterQuery($value)
    {
        if ($value === 'false') {
            $value = false;
        }
        $this->_filterQuery = $value;
    }

    /**
     * Get filter query
     * @return __return_getFilterQuery_type__ __return_getFilterQuery_description__
     */
    public function getFilterQuery()
    {
        return $this->_filterQuery;
    }

    /**
     * Set type options
     * @param __param_options_type__ $options __param_options_description__
     */
    public function setTypeOptions($options)
    {
        $this->_typeOptions = array_merge($this->_typeOptions, $options);
    }

    /**
     * Get type options
     * @return __return_getTypeOptions_type__ __return_getTypeOptions_description__
     */
    public function getTypeOptions()
    {
        return $this->_typeOptions;
    }

    /**
     * Set type
     * @param __param_type_type__ $type __param_type_description__
     * @throws InvalidConfigException __exception_InvalidConfigException_description__
     */
    public function setType($type)
    {
        $acceptableTypes = ['pivot', 'item'];
        if (!in_array($type, $acceptableTypes)) {
            throw new InvalidConfigException('Browser response bundle must be one of the following types: '. implode(', ', $acceptableTypes));
        }
        $this->_type = $type;
    }

    /**
     * Get type
     * @return __return_getType_type__ __return_getType_description__
     */
    public function getType()
    {
        if (is_null($this->_type)) {
            $this->type = 'item';
        }

        return $this->_type;
    }

    /**
     * __method_predictTotal_description__
     * @return __return_predictTotal_type__ __return_predictTotal_description__
     */
    public function predictTotal()
    {
        if ($this->handler) {
            return $this->handler->total;
        }

        return false;
    }

    /**
     * Get total
     * @return __return_getTotal_type__ __return_getTotal_description__
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
     * Set total
     * @param __param_total_type__ $total __param_total_description__
     */
    public function setTotal($total)
    {
        $this->_total = $total;
    }

    /**
     * Get offset
     * @return __return_getOffset_type__ __return_getOffset_description__
     */
    public function getOffset()
    {
        return $this->_offset;
    }

    /**
     * Set offset
     * @param __param_offset_type__ $offset __param_offset_description__
     */
    public function setOffset($offset)
    {
        $this->_offset = $offset;
    }

    /**
     * Get handlers
     * @return __return_getHandlers_type__ __return_getHandlers_description__
     */
    public function getHandlers()
    {
        return [];
    }

    /**
     * Get handler
     * @return __return_getHandler_type__ __return_getHandler_description__
     */
    public function getHandler()
    {
        $handlers = $this->handlers;
        if (isset($this->instructions['handler'])
            && isset($handlers[$this->instructions['handler']])) {
            return Yii::createObject([
                'class' => $handlers[$this->instructions['handler']],
                'bundle' => $this
            ]);
        }
        return false;
    }

    /**
     * __method_handle_description__
     * @return __return_handle_type__ __return_handle_description__
     */
    public function handle()
    {
        if ($this->_handled) { return true; }
        $handler = $this->handler;
        if (!$handler) { return false; }
        $items = $handler->items;
        if ($items === false) { return false; }
        foreach ($items as $item) {
            $this->addItem($item);
        }
        $this->_handled = true;
    }
}
