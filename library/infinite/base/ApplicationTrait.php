<?php
namespace infinite\base;

use Yii;

use \infinite\base\Collector;

trait ApplicationTrait {
	protected $_collectors = [];
	protected $_migrationAliases = [];

	public function isReady() {
		foreach ($this->_collectors as $collector) {
			if (!$collector->isReady()) {
				return false;
			}
		}
		return true;
	}

	public function initializeCollectors() {
		foreach ($this->_collectors as $collector) {
			if (!$collector->initialize()) {
				return false;
			}
		}
		return true;
	}

	public function getCollectors() {
		return $this->_collectors;
	}

	public function setCollectors($collectors) {
		foreach ($collectors as $id => $collector) {
			$this->registerCollector($id, $collector);
		}
	}

	public function registerCollector($id, $collector) {
		if (is_array($collector)) {
			$collector = Yii::createObject($collector);
		}
		$this->_collectors[$id] = $collector;
		return $collector;
	}

	public function registerMigrationAlias($alias) {
		if (!in_array($alias, $this->_migrationAliases)) {
			$this->_migrationAliases[] = $alias;
		}
		return true;
	}

	public function getMigrationAliases() {
		if (!isset(Yii::$app->params['migrationAliases'])) {
			Yii::$app->params['migrationAliases'] = [];
		}
		return array_unique(array_merge(Yii::$app->params['migrationAliases'], $this->_migrationAliases));
	}
}
?>