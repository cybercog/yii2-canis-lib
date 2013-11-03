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
	
	protected $_buckets = [];
	protected $_distributedFields = [];


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
		if (isset($this->_distributedFields[$field])) {
			foreach ($this->getBucket(self::DEFAULT_BUCKET)->toArray() as $item) {
				if (!isset($item->{$field})) { continue; }
				$this->getBucket($field)->add($item->{$field}, $item);
			}
			$this->_distributedFields[$field] = true;
		}
		return true;
	}

	protected function getBucket($name, $distribute = true) {
		if (!isset($this->_buckets[$name])) {
			$this->_buckets[$name] = new Bucket($this);
			if ($distribute && $name !== self::DEFAULT_BUCKET) {
				$this->distribute($name);
			}
		}
		return $this->_buckets[$name];
	}

	public function all($bucket = null) {
		if (is_null($bucket)) {
			$bucket = self::DEFAULT_BUCKET;
		}
		$bucket = $this->getBucket($bucket);
		return $bucket->toArray();
	}

	public function get($item, $bucket = null) {
		if (is_null($bucket)) {
			$bucket = self::DEFAULT_BUCKET;
		}
		$bucket = $this->getBucket($bucket);
		if (!isset($bucket[$item])) {
			return $this->_createBlankItem($item);
		}
		return $bucket[$item];
	}

	protected function _createBlankItem($itemSystemId) {
		$collectorItemClass = $this->collectorItemClass;
		$item = new $collectorItemClass($this, $itemSystemId);
		$this->getBucket(self::DEFAULT_BUCKET)->add($itemSystemId, $item);
		return $item;
	}

	public function register($owner, $itemComponent) {
		$itemComponent = $this->prepareComponent($itemComponent);
		$collectorItemClass = $this->collectorItemClass;
		$item = new $collectorItemClass($this, $itemComponent->systemId, $itemComponent);
		$item->owner = $owner;
		$this->getBucket(self::DEFAULT_BUCKET)->add($itemComponent->systemId, $item);
		return $item;
	}

	public function registerMultiple($owner, $itemComponentSet) {
		$results = [true];
		foreach ($itemComponentSet as $itemComponent) {
			$results[] = $this->register($parent, $itemComponent);
		}
		return min($results);
	}
}
?>