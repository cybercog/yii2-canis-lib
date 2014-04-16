<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\base\collector;

use Yii;

use infinite\helpers\ArrayHelper;

use yii\base\Event;

trait CollectorTrait
{
    protected $_buckets = [];
    protected $_distributedFields = [];

    // Enabling lazyLoad forfeits collectors right to isReady checks
    public $lazyLoad = false;

    public function init()
    {
        parent::init();
        $this->registerMultiple($this->baseOwner, $this->initialItems);
        Yii::$app->collectors->on(Component::EVENT_AFTER_LOAD, [$this, 'beforeRequest']);
    }

    public function getBaseOwner()
    {
        return null;
    }

    public function getInitialItems()
    {
        return [];
    }

    public function beforeRequest(Event $event)
    {
        return true;
    }

    public function isReady()
    {
        return true;
    }

    public function initialize()
    {
        return true;
    }

    public function getCollectorItemClass()
    {
        return 'infinite\\base\\collector\\Item';
    }

    public function prepareComponent($component)
    {
        return $component;
    }

    public function distribute($field)
    {
        if (strpos($field, ':')) {
            $field = strstr($field, ':', true);
        }
        if (!isset($this->_distributedFields[$field])) {
            foreach ($this->bucket as $item) {
                $value = ArrayHelper::getValue($item, $field);
                if (!isset($value)) { continue; }
                if (is_array($value)) {
                    foreach ($value as $itemField) {
                        $this->getBucket($field .':'. $itemField)->add($item->systemId, $item);
                    }
                } else {
                    $this->getBucket($field)->add($value, $item);
                }
            }
            $this->_distributedFields[$field] = true;
        }

        return true;
    }

    public function getBucket($name = null, $distribute = true)
    {
        if (is_null($name)) {
            $name = Collector::DEFAULT_BUCKET;
        }
        if (!isset($this->_buckets[$name])) {
            $this->_buckets[$name] = new Bucket($this);
            if ($distribute && $name !== Collector::DEFAULT_BUCKET) {
                $this->distribute($name);
            }
        }

        return $this->_buckets[$name];
    }

    public function bucket($name = null)
    {
        return $this->getBucket($name);
    }

    public function getAll($bucket = null)
    {
        $bucket = $this->getBucket($bucket);

        return $bucket->toArray();
    }

    public function getOne($item, $bucket = null)
    {
        $bucket = $this->getBucket($bucket);
        if (!isset($bucket[$item])) {
            return $this->_createBlankItem($item);
        }

        return $bucket[$item];
    }

    public function has($item, $bucket = null)
    {
        $bucket = $this->getBucket($bucket);
        if (!isset($bucket[$item])) {
            return false;
        }

        return true;
    }

    protected function _createBlankItem($itemSystemId)
    {
        $collectorItemClass = $this->collectorItemClass;

        $itemComponent = [];
        $itemComponent['class'] = $collectorItemClass;
        $itemComponent['collector'] = $this;
        $itemComponent['systemId'] = $itemSystemId;
        $item = Yii::createObject($itemComponent);
        $this->bucket->add($item->systemId, $item);

        return $item;
    }

    public function register($owner, $itemComponent, $systemId = null)
    {
        $itemComponent = $this->prepareComponent($itemComponent);
        $collectorItemClass = $this->collectorItemClass;
        $itemComponentObject = null;
        if (is_object($itemComponent) && ($itemComponent instanceof CollectedObjectInterface)) {
            $itemComponentObject = $itemComponent;
            $itemComponent = [];
        }

        $itemComponent['class'] = $collectorItemClass;
        $itemComponent['collector'] = $this;
        $itemComponent['owner'] = $owner;

        if (is_array($itemComponent) && !is_null($systemId)) {
            $itemComponent['systemId'] = $systemId;
        }

        $item = Yii::createObject($itemComponent);
        Yii::trace(get_called_class() . ": Registering {$item->systemId}");
        if (isset($itemComponentObject)) {
            $item->object = $itemComponentObject->getCollectedObject($item);
        }
        if (isset($this->bucket[$item->systemId])) {
            $item = $this->mergeExistingItems($this->bucket[$item->systemId], $item);
        } else {
            $this->bucket->add($item->systemId, $item);
        }

        return $item;
    }

    public function mergeExistingItems($originalItem, $newItem)
    {
        if (isset($newItem->object)) {
            $originalItem->object = $newItem->object;
        }

        return $originalItem;
    }

    public function registerMultiple($owner, $itemComponentSet)
    {
        $results = [true];
        foreach ($itemComponentSet as $itemSystemId => $itemComponent) {
            $systemId = null;
            if (!is_numeric($itemSystemId)) {
                $systemId = $itemSystemId;
            }
            $results[] = $this->register($owner, $itemComponent, $systemId);
        }

        return min($results);
    }
}
