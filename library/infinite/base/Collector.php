<?php
namespace infinite\base;

use \infinite\base\Component;

abstract class Collector extends \infinite\base\Component {
	public $primaryBucketField = 'systemId';
	protected $_buckets = [];


	public function isReady() {
		return true;	
	}

	public function initialize() {
		return true;	
	}

	public function getCollectorItemClass() {
		return '\infinite\base\CollectorItem';
	}

	public function getDistributionFields() {
		return [$this->primaryBucketField];
	}

	public function prepareComponent($component) {
		return $component;
	}

	public function distribute(CollectorItem $item) {
		foreach ($this->distributionFields as $field) {
			if (!isset($item->{$field})) { continue; }
			$this->getBucket($field)->add($item->{$field}, $item);
		}
		return true;
	}

	protected function getBucket($name) {
		if (!isset($this->_buckets[$name])) {
			$this->_buckets[$name] = new CollectorBucket($this);
		}
		return $this->_buckets[$name];
	}

	public function all($bucket = null) {
		if (!is_null($bucket)) {
			$bucket = $this->primaryBucketField;
		}
		$bucket = $this->getBucket($bucket);
		return $bucket->toArray();
	}

	public function get($item, $bucket = null) {
		if (!is_null($bucket)) {
			$bucket = $this->primaryBucketField;
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
		if ($this->distribute($item)) {
			return $item;
		}
		return false;
	}

	public function register($itemComponent) {
		$itemComponent = $this->prepareComponent($itemComponent);
		$collectorItemClass = $this->collectorItemClass;
		$item = new $collectorItemClass($this, $itemComponent->systemId, $itemComponent);
		if ($this->distribute($item)) {
			return $item;
		}
		return false;
	}

	public function registerMultiple($itemComponentSet) {
		$results = [true];
		foreach ($itemComponentSet as $itemComponent) {
			$results[] = $this->register($itemComponent);
		}
		return min($results);
	}
}
?>