<?php
namespace infinite\base;

use Yii;

use \infinite\base\Collector;

trait ApplicationTrait {
	protected $_migrationAliases = [];


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