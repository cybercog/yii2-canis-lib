<?php
namespace infinite\base;

use Yii;

use \infinite\base\Collector;

trait ApplicationTrait {
	protected $_migrationAliases = [];
	protected $_modelAliases = [];


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