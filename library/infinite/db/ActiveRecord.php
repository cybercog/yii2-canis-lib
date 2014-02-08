<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;

use Yii;
use ReflectionClass;

use yii\base\ModelEvent;
use infinite\db\ActiveQuery;
use infinite\base\ObjectTrait;
use infinite\base\ModelTrait;
use infinite\db\models\Relation;
use infinite\db\models\Registry;

class ActiveRecord extends \yii\db\ActiveRecord
{
    use ObjectTrait;
    use ModelTrait;

    static public $isAco = true;
    static protected $_cache = [];
    protected $_tabularId;
    public $tabularIdHuman;
    public $descriptorField;
    public $subdescriptorFields = [];
    public static $registryCache = true;
    public static $relationCache = true;


    const FORM_PRIMARY_MODEL = 'primary';

    const TABULAR_PREFIX = '0-';

    /**
     * @event Event an event that is triggered after a failed save.
     */
    const EVENT_AFTER_SAVE_FAIL = 'afterSaveFail';

    public static function getRegistryClass()
    {
        return Registry::className();
    }

    public static function getRelationClass()
    {
        return Relation::className();
    }

    public static function populateRecord($record, $row)
    {
        $relation = [];
        foreach ($row as $key => $value) {
            if (substr($key, 0, 2) === 'r.') {
                $relation[substr($key, 2)] = $value;
                unset($row[$key]);
            }
        }
        if (self::$relationCache && !empty($relation)) {
            \d($relation);exit;
        }
        parent::populateRecord($record, $row);
        if (self::$registryCache) {
            $registryClass = self::getRegistryClass();
            $registryClass::registerObject($record);
        }
    }

    public function setTabularId($value) {
        $this->tabularIdHuman = $value;
        //\d($value, ['showSteps' => 10]);
        $this->_tabularId = self::generateTabularId($value);
    }

    public function getTabularPrefix() {
        return '['. $this->tabularId .']';
    }
    
    public static function generateTabularId($id) 
    {
        if (substr($id, 0, strlen(self::TABULAR_PREFIX)) === self::TABULAR_PREFIX) { return $id; }
        return self::TABULAR_PREFIX.substr(md5($id), 0, 10);
    }

    public static function getPrimaryTabularId() {
        return self::generateTabularId(self::FORM_PRIMARY_MODEL);
    }

    public static function getPrimaryModel($models) {
        if (empty($models)) { return false; }
        foreach ($models as $tabKey => $model) {
            if ($tabKey === self::getPrimaryTabularId(self::baseClassName())) {
                return $model;
            }
        }
        return false;
    }

    public static function parseModelAlias($alias) {
        if (strncmp($alias, ':', 1)) {
            // not an alias
            return $alias;
        }
        $pos = strpos($alias, '\\');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if ($root === ':app') {
            return 'app\models' . substr($alias, $pos);
        } elseif (isset(Yii::$app->modelAliases[$root])) {
            return Yii::$app->modelAliases[$root] . substr($alias, $pos);
        }
        return $alias;
    }
    
    public function getModelAlias() {
        return self::modelAlias();
    }

    public static function modelAlias($className = null) {
        if (is_null($className)) {
            $className = get_called_class();
        } elseif(is_object($className)) {
            $className = get_class($className);
        }
         if (!strncmp($className, ':', 1)) {
            // already an alias
            return $className;
        }
        $class = new ReflectionClass($className);
        if ($class->getNamespaceName() === 'app\models') {
            return ':app\\' . $class->getShortName();
        } elseif (($alias = array_search($class->getNamespaceName(), Yii::$app->modelAliases)) !== false) {
            return $alias .'\\' . $class->getShortName();
        }
        return $className;
    }

    public static function clearCache($model = null) {
        if (is_null($model)) {
            self::$_cache = [];
        } elseif(isset(self::$_cache[$model])) {
            self::$_cache[$model] = [];
        }
        return true;
    }

    public static function get($id, $checkAccess = true) {
        $class = get_called_class();
        $dummy = new $class;
        return self::findOne([$dummy->tableName() .'.'. $dummy->primaryKey()[0] => $id], $checkAccess);
    }

    public static function findOne($where, $checkAccess = true) {
        return self::_findCache('one', $where, $checkAccess);
    }


    public static function findAll($where = false, $checkAccess = true) {
        return self::_findCache('all', $where, $checkAccess);
    }

    protected static function _findCache($type, $where = false, $checkAccess = true)
    {
        if (is_array($where)) {
            ksort($where);
        }
        $model = self::className();
        $key = md5(serialize(['type' => $type, 'where' => $where, 'access' => $checkAccess]));
        if (!isset(self::$_cache[$model])) {
            self::$_cache[$model] = [];
        }
        if (!isset(self::$_cache[$model][$key])) {
            $r = self::find();
            if ($where) {
                $r->where($where);
            }
            if (!$checkAccess AND $r->hasBehavior('Access')) {
                $r->disableAccessCheck();
            }
            $r = $r->$type();
            if ($r) {
                self::$_cache[$model][$key] = $r;
            } else {
                return [];
            }
        }
        return self::$_cache[$model][$key];
    }



    public static function tableExists()
    {
        try {
            self::getTableSchema();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Creates an [[ActiveQuery]] instance.
     * This method is called by [[find()]], [[findBySql()]] and [[count()]] to start a SELECT query.
     * You may override this method to return a customized query (e.g. `CustomerQuery` specified
     * written for querying `Customer` purpose.)
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function createQuery()
    {
        $query = new \infinite\db\ActiveQuery(['modelClass' => get_called_class()]);
        $query->attachBehaviors(static::queryBehaviors());
        return $query;
    }

    public static function isAccessControlled()
    {
        return true;
    }

    public function behaviors()
    {
        return [
            'Date' => [
                'class' => 'infinite\db\behaviors\Date',
            ],
            'Blame' => [
                'class' => 'infinite\db\behaviors\Blame',
            ],
            'ActiveAccess' => [
                'class' => 'infinite\db\behaviors\ActiveAccess',
            ]
        ];
    }


    public static function queryBehaviors()
    {
        return [];
    }

    public function getDescriptor()
    {
        if (isset($this->descriptorField)) {
            if (is_array($this->descriptorField)) {
                $descriptor = [];
                foreach ($this->descriptorField as $field) {
                    if (!empty($this->{$field})) {
                        $descriptor[] = $this->{$field};
                    }
                }
                return implode(' ', $descriptor);
            } else {
                return $this->{$this->descriptorField};
            }
        }
        $try = ['name', 'title', 'created'];
        foreach ($try as $t) {
            if ($this->hasAttribute($t)) {
                return $this->{$t};
            }
        }
        return false;
    }

    public function getSubdescriptor()
    {
        $sub = [];
        foreach ($this->subdescriptorFields as $field) {
            $value = $this->getFieldValue($field);
            if (!empty($value)) {
                $sub[] = $value;
            }
        }
        return $sub;
    }

    public function isForeignField($field)
    {
        return !$this->hasAttribute($field);
    }

    public function getFieldValue($field)
    {
        if (is_array($field)) {
            // first with a value is our winner
            foreach ($field as $subfield) {
                $value = $this->getFieldValue($subfield);
                if (!empty($value)) {
                    return $value;
                }
            }
            return null;
        }
        if ($this->isForeignField($field)) {
            return $this->getForeignFieldValue($field);
        } else {
            return $this->getLocalFieldValue($field);
        }
    }

    public function getLocalFieldValue($field)
    {
        if ($this->hasAttribute($field)) {
            return $this->{$field};
        }
        return null;
    }

    public function getForeignFieldValue($field)
    {
        return null;
    }

    public function checkExistence()
    {
        if (empty($this->primaryKey)) {
            return false;
        }
        return self::find()->pk($this->primaryKey)->count() > 0;
    }

    /**
	 *
	 *
	 * @param unknown $value
	 * @return unknown
	 */
	public function quote($value) {
		if (is_array($value)) {
			foreach ($value as $k => $v) {
				$value[$k] = $this->quote($v);
			}
			return $value;
		}
		if (is_null($value)) { return $value; }
		return $this->db->quoteValue($value);
	}

    /**
     *
     *
     * @todo see if they added an event in the final version of Yii2
     * @param unknown $runValidation (optional)
     * @param unknown $attributes    (optional)
     * @return unknown
     */
    public function save($runValidation=true, $attributes=NULL)
    {
        if (parent::save($runValidation, $attributes)) {
            return true;
        } else {
            $event = new ModelEvent;
            $this->trigger(self::EVENT_AFTER_SAVE_FAIL, $event);
            return false;
        }
    }
}
