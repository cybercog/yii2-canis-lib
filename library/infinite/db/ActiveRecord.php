<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;

use yii\base\ModelEvent;
use infinite\db\ActiveQuery;

class ActiveRecord extends \yii\db\ActiveRecord
{
    static public $isAco = true;
    static protected $_cache = [];

    /**
     * @event Event an event that is triggered after a failed save.
     */
    const EVENT_AFTER_SAVE_FAIL = 'afterSaveFail';


    public $descriptorField;

    public static function clearCache($model = null) {
        if (is_null($model)) {
            self::$_cache = [];
        } elseif(isset(self::$_cache[$model])) {
            self::$_cache[$model] = [];
        }
        return true;
    }

    public static function findOne($where, $access = true) {
        return self::_findCache('one', $where, $access);
    }


    public static function findAll($where, $access = true) {
        return self::_findCache('all', $where, $access);
    }

    protected static function _findCache($type, $where, $access = true)
    {
        ksort($where);
        $model = self::className();
        $key = md5(serialize(['type' => $type, 'where' => $where, 'access' => $access]));
        if (!isset(self::$_cache[$model])) {
            self::$_cache[$model] = [];
        }
        if (!isset(self::$_cache[$model][$key])) {
            $r = self::find()->where($where);
            if (!$access AND $r->hasBehavior('Access')) {
                $r->disableAccess();
            }
            $r = $r->$type();
            if ($r) {
                self::$_cache[$model][$key] = $r;
            }
        }
        return self::$_cache[$model][$key];
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
