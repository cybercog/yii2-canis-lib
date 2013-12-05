<?php
namespace infinite\base;

use Yii;

use infinite\base\Collector;

trait ApplicationTrait {
	use ComponentTrait;
	
	protected $_migrationAliases = [];
	protected $_modelAliases = [];

	public function init() {
		$start = microtime(true);
		parent::init();
		$duration = round((microtime(true) - $start) * 1000, 2);
		Yii::trace("Init took ". $duration .'ms');
	}

	public function registerModelAlias($alias, $namespace) {
		if (strncmp($alias, ':', 1)) {
			$alias = ':' . $alias;
		}
		if (!isset($this->_modelAliases[$alias])) {
			$this->_modelAliases[$alias] = $namespace;
		}
		return true;
	}

	public function getModelAliases() {
		return $this->_modelAliases;
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