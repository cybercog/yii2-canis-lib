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

class ActiveRecord extends \yii\db\ActiveRecord
{
    use \infinite\base\ObjectTrait;

    static public $isAco = true;
    static protected $_cache = [];

    /**
     * @event Event an event that is triggered after a failed save.
     */
    const EVENT_AFTER_SAVE_FAIL = 'afterSaveFail';


    public $descriptorField;

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

    public static function get($id, $access = true) {
        return self::findOne(['id' => $id], $access);
    }

    public static function findOne($where, $access = true) {
        return self::_findCache('one', $where, $access);
    }


    public static function findAll($where = false, $access = true) {
        return self::_findCache('all', $where, $access);
    }

    protected static function _findCache($type, $where = false, $access = true)
    {
        if (is_array($where)) {
            ksort($where);
        }
        $model = self::className();
        $key = md5(serialize(['type' => $type, 'where' => $where, 'access' => $access]));
        if (!isset(self::$_cache[$model])) {
            self::$_cache[$model] = [];
        }
        if (!isset(self::$_cache[$model][$key])) {
            $r = self::find();
            if ($where) {
                $r->where($where);
            }
            if (!$access AND $r->hasBehavior('Access')) {
                $r->disableAccess();
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

    public function behaviors()
    {
        return [
            'Date' => [
                'class' => '\infinite\db\behaviors\Date',
            ],
            'Blame' => [
                'class' => '\infinite\db\behaviors\Blame',
            ]
        ];
    }


    public static function queryBehaviors()
    {
        return [
            'Access' => [
                'class' => '\infinite\db\behaviors\Access',
            ]
        ];
    }

    public function getDescriptor()
    {
        if (isset($this->descriptorField)) {
            return $this->{$this->descriptorField};
        }
        $try = ['name', 'title', 'created'];
        foreach ($try as $t) {
            if ($this->hasAttribute($t)) {
                return $this->{$t};
            }
        }
        return false;
    }

    public function checkExistence()
    {
        if (empty($this->primaryKey)) {
            return false;
        }
        return self::find()->where([$this->primaryKey() => $this->primaryKey])->count > 0;
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
