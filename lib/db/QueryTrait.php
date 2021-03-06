<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db;

use Yii;
use yii\base\ModelEvent;
use yii\db\Query as BaseQuery;

trait QueryTrait
{
    public $disableFutureAccessCheck = false;
    public $ensureSelect;
    protected $_db;

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

    public function prepareBuild($builder)
    {
        parent::prepareBuild($builder);
        if (!empty($this->ensureSelect)) {
            if (!is_array($this->ensureSelect)) {
                $this->ensureSelect = [$this->ensureSelect];
            }
            if (!isset($this->select)) {
                $this->select = [];
            }
            foreach ($this->ensureSelect as $key => $select) {
                if (is_string($key)) {
                    if (!isset($this->select[$key])) {
                        $this->select[$key] = $select;
                    }
                } else {
                    if (!in_array($select, $this->select)) {
                        $this->select[] = $select;
                    }
                }
            }
        }
    }

    public function ensureAccessControl()
    {
        if (!isset($this->modelClass) || $this->disableFutureAccessCheck) {
            return;
        }
        $modelClass = $this->modelClass;
        if ($modelClass::isAccessControlled()) {
            $this->enableAccessCheck();
        }
    }

    public function createCommand($db = null)
    {
        $this->_db = $db;
        $modelEvent = new ModelEvent();
        $this->trigger(Query::EVENT_BEFORE_QUERY, $modelEvent);

        return self::basicCreateCommand($db);
    }

    public function subquerySelf($db = null, $alias = 'inside')
    {
        if (is_null($db) && !is_null($this->_db)) {
            $db = $this->_db;
        }
        $rawSql = '(' . $this->basicCreateCommand($db)->rawSql . ')';
        $this->resetQuery();
        $this->from([$alias => $rawSql]);
    }

    protected function resetQuery()
    {
        foreach (get_class_vars(BaseQuery::className()) as $var => $default) {
            $this->{$var} = $default;
        }
    }

    private function basicCreateCommand($db = null)
    {
        return parent::createCommand($db);
    }

    public function getAccessBehaviorConfiguration()
    {
        return [
            'class' => 'canis\db\behaviors\QueryAccess',
        ];
    }

    public function disableAccessCheck()
    {
        $this->disableFutureAccessCheck = true;
        $this->getBehavior('Access') === null || $this->detachBehavior('Access');

        return $this;
    }

    public function enableAccessCheck()
    {
        $this->disableFutureAccessCheck = false;
        $this->getBehavior('Access') !== null || $this->attachBehavior('Access', $this->accessBehaviorConfiguration);

        return $this;
    }

    public function pk($pk)
    {
        return $this->andWhere([$this->primaryAlias . '.' . $this->primaryTablePk => $pk]);
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
            if (!is_object($schema)) {
                return false;
            }

            return $schema->primaryKey[0];
        }

        return false;
    }

    public function getPrimaryTable($db = null)
    {
        if (is_null($db)) {
            $db = Yii::$app->db;
        }
        if ($this instanceof ActiveQuery) {
            $modelClass = $this->modelClass;

            return $modelClass::tableName();
        } elseif (isset($this->from[0])) {
            $tableName = $this->from[0];
            $tableParts = explode(' ', $this->from[0]);

            return $tableParts[0];
        }

        return false;
    }

    public function generateLikeWhere($like, $operator = 'and')
    {
        $where = [$operator];
        foreach ($like as $column => $value) {
            $id = ':' . md5(microtime(true) . uniqid() . rand(0, 1000));
            $this->params[$id] = $value;
            $where[] = $column . ' LIKE ' . $id;
        }

        return $where;
    }

    public function like($like, $operator = 'and')
    {
        return $this->where($this->generateLikeWhere($like, $operator));
    }

    public function orLike($like, $operator = 'and')
    {
        return $this->orWhere($this->generateLikeWhere($like, $operator));
    }

    public function andLike($like, $operator = 'and')
    {
        return $this->andWhere($this->generateLikeWhere($like, $operator));
    }
}
