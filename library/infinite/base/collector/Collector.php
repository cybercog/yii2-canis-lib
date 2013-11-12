<?php
namespace infinite\base\collector;

use Yii;

use \infinite\base\exceptions\Exception;

use \yii\base\Application;
use \yii\base\Event;

abstract class Collector extends \infinite\base\Component 
{
	const DEFAULT_BUCKET = '__default';
	const EVENT_AFTER_COLLECTOR_INIT = 'afterCollectorInit';
	
	public $id;
	
	protected $_buckets = [];
	protected $_distributedFields = [];

	public function init()
	{
		parent::init();
		Yii::$app->collectors->on(Component::EVENT_AFTER_LOAD, array($this, 'beforeRequest'));
	}

	public function beforeRequest(Event $event) {
		return true;
	}

	public function isReady() {
		return true;	
	}

	public function initialize() {
		return true;	
	}

	public function getCollectorItemClass() {
		return '\infinite\base\collector\Item';
	}

	public function prepareComponent($component) {
		return $component;
	}

	public function distribute($field) {
		if (strpos($field, ':')) {
			$field = strstr($field, ':', true);
		}
		if (!isset($this->_distributedFields[$field])) {
			foreach ($this->bucket as $item) {
				if (!isset($item->{$field})) { continue; }
				if (is_array($item->{$field})) {
					foreach ($item->{$field} as $itemField) {
						$this->getBucket($field .':'. $itemField)->add($item->systemId, $item);
					}
				} else {
					$this->getBucket($field)->add($item->{$field}, $item);
				}
			}
			$this->_distributedFields[$field] = true;
		}
		return true;
	}

	protected function getBucket($name = null, $distribute = true) {
		if (is_null($name)) {
			$name = self::DEFAULT_BUCKET;
		}
		if (!isset($this->_buckets[$name])) {
			$this->_buckets[$name] = new Bucket($this);
			if ($distribute && $name !== self::DEFAULT_BUCKET) {
				$this->distribute($name);
			}
		}
		return $this->_buckets[$name];
	}

	public function bucket($name = null) {
		return $this->getBucket($name);
	}

	public function getAll($bucket = null) {
		$bucket = $this->getBucket($bucket);
		return $bucket->toArray();
	}

	public function getOne($item, $bucket = null) {
		if (is_null($item)) {
			throw new Exception("boom");
		}
		$bucket = $this->getBucket($bucket);
		if (!isset($bucket[$item])) {
			return $this->_createBlankItem($item);
		}
		return $bucket[$item];
	}

	protected function _createBlankItem($itemSystemId) {
		$collectorItemClass = $this->collectorItemClass;

		$itemComponent = [];
		$itemComponent['class'] = $collectorItemClass;
		$itemComponent['collector'] = $this;
		$itemComponent['systemId'] = $itemSystemId;
		$item = Yii::createObject($itemComponent);
		$this->bucket->add($item->systemId, $item);
		return $item;
	}

	public function register($owner, $itemComponent, $systemId = null) {
		$itemComponent = $this->prepareComponent($itemComponent);
		$collectorItemClass = $this->collectorItemClass;
		if (is_array($itemComponent) && !is_null($systemId)) {
			$itemComponent['systemId'] = $systemId;
		}

		if (is_object($itemComponent)) {
			$itemComponent = ['object' => $itemComponent];
		}

		$itemComponent['class'] = $collectorItemClass;
		$itemComponent['collector'] = $this;
		$itemComponent['owner'] = $owner;

		$item = Yii::createObject($itemComponent);
		Yii::trace(get_called_class() . ": Registering {$item->systemId}");
		if (isset($this->bucket[$item->systemId])) {
			if (isset($itemComponent['object'])) {
				$this->bucket[$item->systemId]->object = $itemComponent['object'];
			}
			$item = $this->bucket[$item->systemId];
		} else {
			$this->bucket->add($item->systemId, $item);
		}
		return $item;
	}

	public function registerMultiple($owner, $itemComponentSet) {
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
?>