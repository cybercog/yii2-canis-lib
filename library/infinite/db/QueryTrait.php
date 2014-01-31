<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;

use Yii;
use yii\base\ModelEvent;

trait QueryTrait
{
	public function __clone()
	{
		parent::__clone();
		$this->ensureAccessControl();
	}

    public function init()
    {
        parent::init();
        $this->ensureAccessControl();
    }

    public function ensureAccessControl()
    {
        $modelClass = $this->modelClass;
        if ($modelClass::isAccessControlled()) {
            $this->enableAccessCheck();
        }
    }

    public function createCommand($db = null)
    {
        $modelEvent = new ModelEvent;
        $this->trigger(Query::EVENT_BEFORE_QUERY, $modelEvent);
        return parent::createCommand($db);
    }

    public function getAccessBehaviorConfiguration()
    {
        return [
            'class' => 'infinite\\db\\behaviors\\QueryAccess',
        ];
    }

    public function disableAccessCheck()
    {
        $this->getBehavior('Access') === null || $this->detachBehavior('Access');
        return $this;
    }

    public function enableAccessCheck()
    {
       $this->getBehavior('Access') !== null || $this->attachBehavior('Access', $this->accessBehaviorConfiguration);
       return $this;
    }

    public function count($q = '*', $db = null)
	{
		return parent::count($q, $db);
	}

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