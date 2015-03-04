<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

use Yii;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use yii\base\BootstrapInterface;
use yii\base\Event;
use infinite\caching\Cacher;

/**
 * Component [@doctodo write class description for Component].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Component extends \infinite\base\Component implements IteratorAggregate, ArrayAccess, BootstrapInterface
{
    const EVENT_AFTER_LOAD = 'afterLoad';
    const EVENT_AFTER_INIT = 'afterInit';

    public $cacheTime = false;

    /**
     * @var __var__collectors_type__ __var__collectors_description__
     */
    protected $_collectors = [];
    /**
     * @var __var__init_collectors_type__ __var__init_collectors_description__
     */
    protected $_init_collectors = [];
    /**
     * @var __var__loaded_type__ __var__loaded_description__
     */
    protected $_loaded = false;

    /**
     * __method_bootstrap_description__.
     *
     * @param __param_app_type__ $app __param_app_description__
     */
    public function bootstrap($app)
    {
        Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, [$this, 'beforeRequest'], null, false);
    }

    /**
     * __method_beforeRequest_description__.
     *
     * @param __param_event_type__ $event __param_event_description__
     *
     * @return __return_beforeRequest_type__ __return_beforeRequest_description__
     */
    public function beforeRequest($event)
    {
        if (empty($this->_init_collectors)) {
            return;
        }
        // load
        $this->load();
    }

    protected function loadFromCache($cacheKey)
    {
        if (!$this->cacheTime) {
            return false;
        }
        Yii::beginProfile('Collector::loadFromCache');
        if (($collectors = Cacher::get($cacheKey))) {
            $this->_collectors = $collectors;
            Yii::trace('Restored collectors from cache');
            Yii::endProfile('Collector::loadFromCache');

            return true;
        }
        Yii::endProfile('Collector::loadFromCache');

        return false;
    }

    protected function saveCache($cacheKey)
    {
        if (!$this->cacheTime) {
            return false;
        }
        //\d($this->_collectors);exit;
        Cacher::set($cacheKey, $this->_collectors, $this->cacheTime);

        return true;
    }

    /**
     * __method_load_description__.
     */
    public function load()
    {
        if (!$this->_loaded) {
            $cacheKey = [__CLASS__, md5(json_encode($this->_init_collectors))];
            if ($this->loadFromCache($cacheKey)) {
                $this->_loaded = true;

                return true;
            }

            Yii::beginProfile(__CLASS__.'::'.__FUNCTION__);
            foreach ($this->_init_collectors as $id => $collector) {
                $this->internalRegisterCollector($id, $collector);
            }
            $this->_init_collectors = null;

            // initialize
            Yii::beginProfile(__CLASS__.'::'.__FUNCTION__.':::afterLoad');
            $this->trigger(self::EVENT_AFTER_LOAD);
            Yii::endProfile(__CLASS__.'::'.__FUNCTION__.':::afterLoad');

            // final round
            Yii::beginProfile(__CLASS__.'::'.__FUNCTION__.':::afterInit');
            $this->trigger(self::EVENT_AFTER_INIT);
            Yii::endProfile(__CLASS__.'::'.__FUNCTION__.':::afterInit');
            Yii::endProfile(__CLASS__.'::'.__FUNCTION__);
            $this->_loaded = true;
            $this->saveCache($cacheKey);
        }
    }

    /**
     * __method_areReady_description__.
     *
     * @return __return_areReady_type__ __return_areReady_description__
     */
    public function areReady()
    {
        $this->load();
        Yii::beginProfile(__CLASS__.'::'.__FUNCTION__);
        foreach ($this->_collectors as $collector) {
            if (!is_object($collector)) {
                continue;
            }
            Yii::beginProfile(__CLASS__.'::'.__FUNCTION__.'::'.$collector->systemId);
            if (!$collector->isReady()) {
                Yii::endProfile(__CLASS__.'::'.__FUNCTION__.'::'.$collector->systemId);
                Yii::endProfile(__CLASS__.'::'.__FUNCTION__);

                return false;
            }
            Yii::endProfile(__CLASS__.'::'.__FUNCTION__.'::'.$collector->systemId);
        }
        Yii::endProfile(__CLASS__.'::'.__FUNCTION__);

        return true;
    }

    /**
     * __method_initialize_description__.
     *
     * @return __return_initialize_type__ __return_initialize_description__
     */
    public function initialize()
    {
        foreach ($this->_collectors as $collector) {
            if (!is_object($collector)) {
                continue;
            }
            if (!$collector->initialize()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get collectors.
     *
     * @return __return_getCollectors_type__ __return_getCollectors_description__
     */
    public function getCollectors()
    {
        return $this->_collectors;
    }

    /**
     * Set collectors.
     *
     * @param __param_collectors_type__ $collectors __param_collectors_description__
     */
    public function setCollectors($collectors)
    {
        $this->_init_collectors = $collectors;
    }

    /**
     * __method_onAfterLoad_description__.
     *
     * @param __param_action_type__ $action __param_action_description__
     *
     * @return __return_onAfterLoad_type__ __return_onAfterLoad_description__
     */
    public function onAfterLoad($action)
    {
        return $this->on(self::EVENT_AFTER_LOAD, $action);
    }

    /**
     * __method_onAfterInit_description__.
     *
     * @param __param_action_type__ $action __param_action_description__
     *
     * @return __return_onAfterInit_type__ __return_onAfterInit_description__
     */
    public function onAfterInit($action)
    {
        return $this->on(self::EVENT_AFTER_INIT, $action);
    }

    /**
     * __method_internalRegisterCollector_description__.
     *
     * @param __param_id_type__        $id        __param_id_description__
     * @param __param_collector_type__ $collector __param_collector_description__
     *
     * @return __return_internalRegisterCollector_type__ __return_internalRegisterCollector_description__
     */
    protected function internalRegisterCollector($id, $collector)
    {
        Yii::beginProfile(__CLASS__.'::'.__FUNCTION__.'::'.$id);
        if (is_array($collector) && empty($collector['lazyLoad'])) {
            $collector = Yii::createObject($collector);
            $collector->systemId = $id;
        }
        $this->_collectors[$id] = $collector;
        Yii::endProfile(__CLASS__.'::'.__FUNCTION__.'::'.$id);

        return $collector;
    }

    /**
     * __method_toArray_description__.
     *
     * @return __return_toArray_type__ __return_toArray_description__
     */
    public function toArray()
    {
        return $this->_collectors;
    }

    /**
     * Get sleeping count.
     *
     * @return __return_getSleepingCount_type__ __return_getSleepingCount_description__
     */
    public function getSleepingCount()
    {
        return count($this->sleeping());
    }

    /**
     * __method_sleeping_description__.
     *
     * @return __return_sleeping_type__ __return_sleeping_description__
     */
    public function sleeping()
    {
        $s = [];
        foreach ($this->_collectors as $c) {
            if (is_array($c)) {
                $s[] = $c;
            }
        }

        return $s;
    }

    /**
     * Returns an iterator for traversing the attributes in the model.
     * This method is required by the interface IteratorAggregate.
     *
     * @return ArrayIterator an iterator for traversing the items in the list.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_collectors);
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `isset($model[$offset])`.
     *
     * @param mixed $offset the offset to check on
     *
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_collectors);
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$value = $model[$offset];`.
     *
     * @param mixed $offset the offset to retrieve element.
     *
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            if (is_array($this->_collectors[$offset])) {
                $this->_collectors[$offset] = Yii::createObject($this->_collectors[$offset]);
                $this->_collectors[$offset]->systemId = $offset;
                $this->_collectors[$offset]->beforeRequest(new Event());
            }

            return $this->_collectors[$offset];
        }

        return;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `$model[$offset] = $item;`.
     *
     * @param integer $offset the offset to set element
     * @param mixed   $item   the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->_collectors[$offset] = $item;
    }

    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface `ArrayAccess`.
     * It is implicitly called when you use something like `unset($model[$offset])`.
     *
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        unset($this->_collectors[$offset]);
    }
}
