<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\base\collector;

use ArrayAccess;
use ArrayIterator;
use teal\caching\Cacher;
use IteratorAggregate;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;

/**
 * Component [[@doctodo class_description:teal\base\collector\Component]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Component extends \teal\base\Component implements IteratorAggregate, ArrayAccess, BootstrapInterface
{
    const EVENT_AFTER_LOAD = 'afterLoad';
    const EVENT_AFTER_INIT = 'afterInit';

    /**
     * @var [[@doctodo var_type:cacheTime]] [[@doctodo var_description:cacheTime]]
     */
    public $cacheTime = false;

    /**
     * @var [[@doctodo var_type:_collectors]] [[@doctodo var_description:_collectors]]
     */
    protected $_collectors = [];
    /**
     * @var [[@doctodo var_type:_init_collectors]] [[@doctodo var_description:_init_collectors]]
     */
    protected $_init_collectors = [];
    /**
     * @var [[@doctodo var_type:_loaded]] [[@doctodo var_description:_loaded]]
     */
    protected $_loaded = false;

    /**
     * [[@doctodo method_description:bootstrap]].
     *
     * @param [[@doctodo param_type:app]] $app [[@doctodo param_description:app]]
     */
    public function bootstrap($app)
    {
        Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, [$this, 'beforeRequest'], null, false);
    }

    /**
     * [[@doctodo method_description:beforeRequest]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeRequest]] [[@doctodo return_description:beforeRequest]]
     */
    public function beforeRequest($event)
    {
        if (empty($this->_init_collectors)) {
            return;
        }
        // load
        $this->load();
    }

    /**
     * [[@doctodo method_description:loadFromCache]].
     *
     * @param [[@doctodo param_type:cacheKey]] $cacheKey [[@doctodo param_description:cacheKey]]
     *
     * @return [[@doctodo return_type:loadFromCache]] [[@doctodo return_description:loadFromCache]]
     */
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

    /**
     * [[@doctodo method_description:saveCache]].
     *
     * @param [[@doctodo param_type:cacheKey]] $cacheKey [[@doctodo param_description:cacheKey]]
     *
     * @return [[@doctodo return_type:saveCache]] [[@doctodo return_description:saveCache]]
     */
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
     * [[@doctodo method_description:load]].
     *
     * @return [[@doctodo return_type:load]] [[@doctodo return_description:load]]
     */
    public function load()
    {
        if (!$this->_loaded) {
            $cacheKey = [__CLASS__, md5(json_encode($this->_init_collectors))];
            if ($this->loadFromCache($cacheKey)) {
                $this->_loaded = true;

                return true;
            }

            Yii::beginProfile(__CLASS__ . '::' . __FUNCTION__);
            foreach ($this->_init_collectors as $id => $collector) {
                $this->internalRegisterCollector($id, $collector);
            }
            $this->_init_collectors = null;

            // initialize
            Yii::beginProfile(__CLASS__ . '::' . __FUNCTION__ . ':::afterLoad');
            $this->trigger(self::EVENT_AFTER_LOAD);
            Yii::endProfile(__CLASS__ . '::' . __FUNCTION__ . ':::afterLoad');

            // final round
            Yii::beginProfile(__CLASS__ . '::' . __FUNCTION__ . ':::afterInit');
            $this->trigger(self::EVENT_AFTER_INIT);
            Yii::endProfile(__CLASS__ . '::' . __FUNCTION__ . ':::afterInit');
            Yii::endProfile(__CLASS__ . '::' . __FUNCTION__);
            $this->_loaded = true;
            $this->saveCache($cacheKey);
        }
    }

    /**
     * [[@doctodo method_description:areReady]].
     *
     * @return [[@doctodo return_type:areReady]] [[@doctodo return_description:areReady]]
     */
    public function areReady()
    {
        $this->load();
        Yii::beginProfile(__CLASS__ . '::' . __FUNCTION__);
        foreach ($this->_collectors as $collector) {
            if (!is_object($collector)) {
                continue;
            }
            Yii::beginProfile(__CLASS__ . '::' . __FUNCTION__ . '::' . $collector->systemId);
            if (!$collector->isReady()) {
                Yii::endProfile(__CLASS__ . '::' . __FUNCTION__ . '::' . $collector->systemId);
                Yii::endProfile(__CLASS__ . '::' . __FUNCTION__);

                return false;
            }
            Yii::endProfile(__CLASS__ . '::' . __FUNCTION__ . '::' . $collector->systemId);
        }
        Yii::endProfile(__CLASS__ . '::' . __FUNCTION__);

        return true;
    }

    /**
     * [[@doctodo method_description:initialize]].
     *
     * @return [[@doctodo return_type:initialize]] [[@doctodo return_description:initialize]]
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
     * @return [[@doctodo return_type:getCollectors]] [[@doctodo return_description:getCollectors]]
     */
    public function getCollectors()
    {
        return $this->_collectors;
    }

    /**
     * Set collectors.
     *
     * @param [[@doctodo param_type:collectors]] $collectors [[@doctodo param_description:collectors]]
     */
    public function setCollectors($collectors)
    {
        $this->_init_collectors = $collectors;
    }

    /**
     * [[@doctodo method_description:onAfterLoad]].
     *
     * @param [[@doctodo param_type:action]] $action [[@doctodo param_description:action]]
     *
     * @return [[@doctodo return_type:onAfterLoad]] [[@doctodo return_description:onAfterLoad]]
     */
    public function onAfterLoad($action)
    {
        return $this->on(self::EVENT_AFTER_LOAD, $action);
    }

    /**
     * [[@doctodo method_description:onAfterInit]].
     *
     * @param [[@doctodo param_type:action]] $action [[@doctodo param_description:action]]
     *
     * @return [[@doctodo return_type:onAfterInit]] [[@doctodo return_description:onAfterInit]]
     */
    public function onAfterInit($action)
    {
        return $this->on(self::EVENT_AFTER_INIT, $action);
    }

    /**
     * [[@doctodo method_description:internalRegisterCollector]].
     *
     * @param [[@doctodo param_type:id]]        $id        [[@doctodo param_description:id]]
     * @param [[@doctodo param_type:collector]] $collector [[@doctodo param_description:collector]]
     *
     * @return [[@doctodo return_type:internalRegisterCollector]] [[@doctodo return_description:internalRegisterCollector]]
     */
    protected function internalRegisterCollector($id, $collector)
    {
        Yii::beginProfile(__CLASS__ . '::' . __FUNCTION__ . '::' . $id);
        if (is_array($collector) && empty($collector['lazyLoad'])) {
            $collector = Yii::createObject($collector);
            $collector->systemId = $id;
        }
        $this->_collectors[$id] = $collector;
        Yii::endProfile(__CLASS__ . '::' . __FUNCTION__ . '::' . $id);

        return $collector;
    }

    /**
     * [[@doctodo method_description:toArray]].
     *
     * @return [[@doctodo return_type:toArray]] [[@doctodo return_description:toArray]]
     */
    public function toArray()
    {
        return $this->_collectors;
    }

    /**
     * Get sleeping count.
     *
     * @return [[@doctodo return_type:getSleepingCount]] [[@doctodo return_description:getSleepingCount]]
     */
    public function getSleepingCount()
    {
        return count($this->sleeping());
    }

    /**
     * [[@doctodo method_description:sleeping]].
     *
     * @return [[@doctodo return_type:sleeping]] [[@doctodo return_description:sleeping]]
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
