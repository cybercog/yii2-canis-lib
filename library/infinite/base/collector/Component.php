<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

use ArrayAccess;
use ArrayIterator;
use infinite\caching\Cacher;
use IteratorAggregate;
use Yii;
use yii\base\BootstrapInterface;
use yii\base\Event;

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
     */
    protected $_collectors = [];
    /**
     */
    protected $_init_collectors = [];
    /**
     */
    protected $_loaded = false;

    /**
     *
     */
    public function bootstrap($app)
    {
        Yii::$app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, [$this, 'beforeRequest'], null, false);
    }

    /**
     *
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
     *
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
     *
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
     */
    public function getCollectors()
    {
        return $this->_collectors;
    }

    /**
     * Set collectors.
     */
    public function setCollectors($collectors)
    {
        $this->_init_collectors = $collectors;
    }

    /**
     *
     */
    public function onAfterLoad($action)
    {
        return $this->on(self::EVENT_AFTER_LOAD, $action);
    }

    /**
     *
     */
    public function onAfterInit($action)
    {
        return $this->on(self::EVENT_AFTER_INIT, $action);
    }

    /**
     *
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
     *
     */
    public function toArray()
    {
        return $this->_collectors;
    }

    /**
     * Get sleeping count.
     */
    public function getSleepingCount()
    {
        return count($this->sleeping());
    }

    /**
     *
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
