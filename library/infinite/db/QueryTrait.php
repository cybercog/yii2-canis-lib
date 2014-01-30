<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;

use Yii;
trait QueryTrait
{
	public function getPrimaryAlias($db = null)
	{
		if (is_null($db)) {
			$db = Yii::$app->db;
		}
		if (isset($this->from[0])) {
			$tableName = $this->from[0];
			$tableParts = explode(' ', $this->from[0]);
			if (count($tableParts) > 1) {
				return $tableParts[1];
			} else {
				return $tableParts[0];
			}
		} elseif ($this instanceof ActiveQuery) {
			$modelClass = $this->modelClass;
			return $modelClass::tableName();
		}
		return false;
	}

	public function getPrimaryTablePk($db = null)
	{
		if (is_null($db)) {
			$db = Yii::$app->db;
		}
		$tableName = $this->getPrimaryTable($db);
		if ($tableName) {
			$schema = $db->getTableSchema($tableName);
			return $schema->primaryKey[0];
		}
		return false;
	}

	public function getPrimaryTable($db = null)
	{
		if (is_null($db)) {
			$db = Yii::$app->db;
		}
		if (isset($this->from[0])) {
			$tableName = $this->from[0];
			$tableParts = explode(' ', $this->from[0]);
			return $tableParts[0];
		} elseif ($this instanceof ActiveQuery) {
			$modelClass = $this->modelClass;
			return $modelClass::tableName();
		}
		return false;
	}
}