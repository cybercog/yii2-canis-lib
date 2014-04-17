<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db;

use Yii;
use ReflectionClass;

use yii\base\ModelEvent;
use infinite\base\ObjectTrait;
use infinite\base\ModelTrait;
use infinite\db\models\Relation;
use infinite\db\models\Registry;
use infinite\caching\Cacher;

/**
 * ActiveRecord is the model class for table "{{%active_record}}".
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class ActiveRecord extends \yii\db\ActiveRecord
{
    use ObjectTrait;
    use ModelTrait;

    /**
     * @var __var_tabularIdHuman_type__ __var_tabularIdHuman_description__
     */
    public $tabularIdHuman;
    /**
     * @var __var_descriptorField_type__ __var_descriptorField_description__
     */
    public $descriptorField;

    /**
     * @var __var_subdescriptorFields_type__ __var_subdescriptorFields_description__
     */
    public $subdescriptorFields = [];
    /**
     * @var __var__wasDirty_type__ __var__wasDirty_description__
     */
    protected $_wasDirty = false;
    /**
     * @var __var__tabularId_type__ __var__tabularId_description__
     */
    protected $_tabularId;

    /**
     * @var __var_queryClass_type__ __var_queryClass_description__
     */
    public static $queryClass;
    /**
     * @var __var_registryCache_type__ __var_registryCache_description__
     */
    public static $registryCache = true;
    /**
     * @var __var_relationCache_type__ __var_relationCache_description__
     */
    public static $relationCache = true;
    /**
     * @var __var_isAco_type__ __var_isAco_description__
     */
    public static $isAco = true;
    /**
     * @var __var_groupCache_type__ __var_groupCache_description__
     */
    public static $groupCache = false;

    /**
     * @var __var__cache_type__ __var__cache_description__
     */
    protected static $_cache = [];

    const FORM_PRIMARY_MODEL = 'primary';
    const TABULAR_PREFIX = '0-';

    /**
     * @event Event an event that is triggered after a failed save.
     */
    const EVENT_AFTER_SAVE_FAIL = 'afterSaveFail';

    /**
    * @inheritdoc
    **/
    public function beforeSave($insert)
    {
        if (!empty($this->dirtyAttributes)) {
            $this->_wasDirty = true;
        }

        return parent::beforeSave($insert);
    }

    /**
    * @inheritdoc
    **/
    public function afterSave($insert)
    {
        $result = parent::afterSave($insert);
        if (static::$groupCache && $this->wasDirty) {
            Cacher::invalidateGroup(self::cacheGroupKey());
        }
        $this->_wasDirty = false;

        return $result;
    }

    /**
     * Get was dirty
     * @return __return_getWasDirty_type__ __return_getWasDirty_description__
     */
    public function getWasDirty()
    {
        return $this->_wasDirty;
    }

    /**
     * __method_modelPrefix_description__
     * @return __return_modelPrefix_type__ __return_modelPrefix_description__
     */
    public static function modelPrefix()
    {
        return substr(strtoupper(sha1(get_called_class())), 0, 8);
    }

    /**
     * __method_cacheGroupKey_description__
     * @return __return_cacheGroupKey_type__ __return_cacheGroupKey_description__
     */
    public static function cacheGroupKey()
    {
        return 'model:' . get_called_class();
    }

    /**
     * __method_cacheDependency_description__
     * @return __return_cacheDependency_type__ __return_cacheDependency_description__
     */
    public static function cacheDependency()
    {
        return Cacher::groupDependency(self::cacheGroupKey());
    }

    /**
    * @inheritdoc
    **/
    public static function populateRecord($record, $row)
    {
        $relation = [];
        $orow = $row;
        parent::populateRecord($record, $row);
        if (static::$registryCache) {
            $registryClass = Yii::$app->classes['Registry'];
            $registryClass::registerObject($record);
        }
    }

    /**
     * Set tabular
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setTabularId($value)
    {
        $this->tabularIdHuman = $value;
        $this->_tabularId = self::generateTabularId($value);
    }

    /**
     * Get tabular prefix
     * @return __return_getTabularPrefix_type__ __return_getTabularPrefix_description__
     */
    public function getTabularPrefix()
    {
        return '['. $this->tabularId .']';
    }

    /**
     * __method_generateTabularId_description__
     * @param __param_id_type__ $id __param_id_description__
     * @return __return_generateTabularId_type__ __return_generateTabularId_description__
     */
    public static function generateTabularId($id)
    {
        if (substr($id, 0, strlen(self::TABULAR_PREFIX)) === self::TABULAR_PREFIX) { return $id; }

        return self::TABULAR_PREFIX.substr(md5($id), 0, 10);
    }

    /**
     * Get primary tabular
     * @return __return_getPrimaryTabularId_type__ __return_getPrimaryTabularId_description__
     */
    public static function getPrimaryTabularId()
    {
        return self::generateTabularId(self::FORM_PRIMARY_MODEL);
    }

    /**
     * Get primary model
     * @param __param_models_type__ $models __param_models_description__
     * @return __return_getPrimaryModel_type__ __return_getPrimaryModel_description__
     */
    public static function getPrimaryModel($models)
    {
        if (empty($models)) { return false; }
        foreach ($models as $tabKey => $model) {
            if ($tabKey === self::getPrimaryTabularId(self::baseClassName())) {
                return $model;
            }
        }

        return false;
    }

    /**
     * __method_parseModelAlias_description__
     * @param __param_alias_type__ $alias __param_alias_description__
     * @return __return_parseModelAlias_type__ __return_parseModelAlias_description__
     */
    public static function parseModelAlias($alias)
    {
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

    /**
     * Get model alias
     * @return __return_getModelAlias_type__ __return_getModelAlias_description__
     */
    public function getModelAlias()
    {
        return self::modelAlias();
    }

    /**
     * __method_modelAlias_description__
     * @param __param_className_type__ $className __param_className_description__ [optional]
     * @return __return_modelAlias_type__ __return_modelAlias_description__
     */
    public static function modelAlias($className = null)
    {
        if (is_null($className)) {
            $className = get_called_class();
        } elseif (is_object($className)) {
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

    /**
     * __method_clearCache_description__
     * @param __param_model_type__ $model __param_model_description__ [optional]
     * @return __return_clearCache_type__ __return_clearCache_description__
     */
    public static function clearCache($model = null)
    {
        if (is_null($model)) {
            self::$_cache = [];
        } elseif (isset(self::$_cache[$model])) {
            self::$_cache[$model] = [];
        }

        return true;
    }

    /**
     * Get
     * @param __param_id_type__ $id __param_id_description__
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
     * @return __return_get_type__ __return_get_description__
     */
    public static function get($id, $checkAccess = true)
    {
        $class = get_called_class();
        $dummy = new $class;

        return self::findOne([$dummy->tableName() .'.'. $dummy->primaryKey()[0] => $id], $checkAccess);
    }

    /**
    * @inheritdoc
    **/
    public static function findOne($where, $checkAccess = true)
    {
        return self::_findCache('one', $where, $checkAccess);
    }

    /**
    * @inheritdoc
    **/
    public static function findAll($where = false, $checkAccess = true)
    {
        return self::_findCache('all', $where, $checkAccess);
    }

    /**
     * __method_findAllCache_description__
     * @param boolean $where __param_where_description__ [optional]
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
     * @return __return_findAllCache_type__ __return_findAllCache_description__
     */
    public static function findAllCache($where = false, $checkAccess = true)
    {
        return self::_findCache('all', $where, $checkAccess);
    }

    /**
     * __method__findCache_description__
     * @param __param_type_type__ $type __param_type_description__
     * @param boolean $where __param_where_description__ [optional]
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
     * @return __return__findCache_type__ __return__findCache_description__
     */
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
            if (!$checkAccess && $r->hasBehavior('Access')) {
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

    /**
     * __method_tableExists_description__
     * @return __return_tableExists_type__ __return_tableExists_description__
     */
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
     *
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find()
    {
        if (is_null(static::$queryClass)) {
            $queryClass = 'infinite\\db\\ActiveQuery';
        } else {
            $queryClass = static::$queryClass;
        }
        $query = new $queryClass(get_called_class());
        $query->attachBehaviors(static::queryBehaviors());

        return $query;
    }

    /**
     * __method_isAccessControlled_description__
     * @return __return_isAccessControlled_type__ __return_isAccessControlled_description__
     */
    public static function isAccessControlled()
    {
        return true;
    }

    /**
    * @inheritdoc
    **/
    public function behaviors()
    {
        return [
            'Date' => [
                'class' => 'infinite\db\behaviors\Date',
            ],
            'Blame' => [
                'class' => 'infinite\db\behaviors\Blame',
            ],
            'Archivable' => [
                'class' => 'infinite\db\behaviors\ActiveArchivable',
            ],
            'ActiveAccess' => [
                'class' => 'infinite\db\behaviors\ActiveAccess',
            ]
        ];
    }

    /**
     * __method_queryBehaviors_description__
     * @return __return_queryBehaviors_type__ __return_queryBehaviors_description__
     */
    public static function queryBehaviors()
    {
        return [
            'Archivable' => [
                'class' => 'infinite\db\behaviors\QueryArchivable',
            ],
        ];
    }

    /**
     * Get descriptor
     * @return __return_getDescriptor_type__ __return_getDescriptor_description__
     */
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

    /**
     * Get subdescriptor
     * @return __return_getSubdescriptor_type__ __return_getSubdescriptor_description__
     */
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

    /**
     * __method_isForeignField_description__
     * @param __param_field_type__ $field __param_field_description__
     * @return __return_isForeignField_type__ __return_isForeignField_description__
     */
    public function isForeignField($field)
    {
        return !$this->hasAttribute($field);
    }

    /**
     * Get field value
     * @param __param_field_type__ $field __param_field_description__
     * @return __return_getFieldValue_type__ __return_getFieldValue_description__
     */
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

    /**
     * Get local field value
     * @param __param_field_type__ $field __param_field_description__
     * @return __return_getLocalFieldValue_type__ __return_getLocalFieldValue_description__
     */
    public function getLocalFieldValue($field)
    {
        if ($this->hasAttribute($field)) {
            return $this->{$field};
        }

        return null;
    }

    /**
     * Get foreign field value
     * @param __param_field_type__ $field __param_field_description__
     * @return __return_getForeignFieldValue_type__ __return_getForeignFieldValue_description__
     */
    public function getForeignFieldValue($field)
    {
        return null;
    }

    /**
     * __method_checkExistence_description__
     * @return __return_checkExistence_type__ __return_checkExistence_description__
     */
    public function checkExistence()
    {
        if (empty($this->primaryKey)) {
            return false;
        }

        return self::find()->pk($this->primaryKey)->count() > 0;
    }

    /**
     * __method_quote_description__
     * @param unknown $value
     * @return unknown
     */
    public function quote($value)
    {
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
     * __method_save_description__
     * @param unknown $runValidation (optional)
     * @param unknown $attributes    (optional)
     * @return unknown
     * @todo see if they added an event in the final version of Yii2
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
