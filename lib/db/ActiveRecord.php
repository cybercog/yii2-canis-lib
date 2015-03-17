<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db;

use canis\base\ModelTrait;
use canis\base\ObjectTrait;
use canis\caching\Cacher;
use canis\db\models\Registry;
use canis\db\models\Relation;
use ReflectionClass;
use Yii;
use yii\base\ModelEvent;
use yii\helpers\Url;

/**
 * ActiveRecord is the model class for table "{{%active_record}}".
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveRecord extends \yii\db\ActiveRecord
{
    use ObjectTrait;
    use ModelTrait;

    /**
     * @var [[@doctodo var_type:tabularIdHuman]] [[@doctodo var_description:tabularIdHuman]]
     */
    public $tabularIdHuman;
    /**
     * @var [[@doctodo var_type:descriptorField]] [[@doctodo var_description:descriptorField]]
     */
    public $descriptorField;
    /**
     * @var [[@doctodo var_type:descriptorLabel]] [[@doctodo var_description:descriptorLabel]]
     */
    public $descriptorLabel = 'Name';
    /**
     * @var [[@doctodo var_type:shortDescriptorField]] [[@doctodo var_description:shortDescriptorField]]
     */
    public $shortDescriptorField = false;
    /**
     * @var [[@doctodo var_type:shortDescriptorLength]] [[@doctodo var_description:shortDescriptorLength]]
     */
    public $shortDescriptorLength = 100;
    /**
     * @var [[@doctodo var_type:_wasDirty]] [[@doctodo var_description:_wasDirty]]
     */
    protected $_wasDirty = false;
    /**
     * @var [[@doctodo var_type:_tabularId]] [[@doctodo var_description:_tabularId]]
     */
    protected $_tabularId;

    /**
     * @var [[@doctodo var_type:queryClass]] [[@doctodo var_description:queryClass]]
     */
    public static $queryClass;
    /**
     * @var [[@doctodo var_type:registryCache]] [[@doctodo var_description:registryCache]]
     */
    public static $registryCache = true;
    /**
     * @var [[@doctodo var_type:relationCache]] [[@doctodo var_description:relationCache]]
     */
    public static $relationCache = true;
    /**
     * @var [[@doctodo var_type:isAco]] [[@doctodo var_description:isAco]]
     */
    public static $isAco = true;
    /**
     * @var [[@doctodo var_type:groupCache]] [[@doctodo var_description:groupCache]]
     */
    public static $groupCache = false;

    /**
     * @var [[@doctodo var_type:_specialFields]] [[@doctodo var_description:_specialFields]]
     */
    protected $_specialFields = [];

    /**
     * @var [[@doctodo var_type:_cache]] [[@doctodo var_description:_cache]]
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
     */
    public function __get($name)
    {
        if (isset($this->_specialFields[$name])) {
            return $this->_specialFields[$name];
        } else {
            return parent::__get($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if (substr($name, 0, 2) === '__') {
            // special field
            $fieldParts = explode('*', substr($name, 2));
            if (count($fieldParts) === 2) {
                if (!isset($this->_specialFields[$fieldParts[0]])) {
                    $this->_specialFields[$fieldParts[0]] = [];
                }
                $this->_specialFields[$fieldParts[0]][$fieldParts[1]] = $value;
            }
        } elseif (isset($this->_specialFields[$name])) {
            $this->_specialFields[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function __isset($name)
    {
        if (isset($this->_specialFields[$name])) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __unset($name)
    {
        if (isset($this->_specialFields[$name])) {
            unset($this->_specialFields[$name]);
        } else {
            parent::__unset($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (substr($name, 0, 2) === '__') {
            return true;
        }

        return parent::canSetProperty($name, $checkVars, $checkBehaviors);
    }
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (!empty($this->dirtyAttributes)) {
            $this->_wasDirty = true;
        }

        return parent::beforeSave($insert);
    }

    /**
     * Get special fields.
     *
     * @return [[@doctodo return_type:getSpecialFields]] [[@doctodo return_description:getSpecialFields]]
     */
    public function getSpecialFields()
    {
        return $this->_specialFields;
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        $result = parent::afterSave($insert, $changedAttributes);
        if (static::$groupCache && $this->wasDirty) {
            Cacher::invalidateGroup(static::cacheGroupKey());
        }
        $this->_wasDirty = false;

        return $result;
    }

    /**
     * [[@doctodo method_description:badFields]].
     *
     * @return [[@doctodo return_type:badFields]] [[@doctodo return_description:badFields]]
     */
    public function badFields()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    protected function resolveFields(array $fields, array $expand)
    {
        $fields = parent::resolveFields($fields, $expand);
        foreach ($this->badFields() as $badField) {
            unset($fields[$badField]);
        }
        $fields['descriptor'] = 'descriptor';
        $fields['subdescriptor'] = 'subdescriptor';
        $fields['icon'] = 'icon';

        return $fields;
    }
    /**
     * Get was dirty.
     *
     * @return [[@doctodo return_type:getWasDirty]] [[@doctodo return_description:getWasDirty]]
     */
    public function getWasDirty()
    {
        return $this->_wasDirty;
    }

    /**
     * [[@doctodo method_description:modelPrefix]].
     *
     * @return [[@doctodo return_type:modelPrefix]] [[@doctodo return_description:modelPrefix]]
     */
    public static function modelPrefix()
    {
        return substr(strtoupper(sha1(get_called_class())), 0, 8);
    }

    /**
     * [[@doctodo method_description:cacheGroupKey]].
     *
     * @return [[@doctodo return_type:cacheGroupKey]] [[@doctodo return_description:cacheGroupKey]]
     */
    public static function cacheGroupKey()
    {
        return 'model:' . get_called_class();
    }

    /**
     * [[@doctodo method_description:cacheDependency]].
     *
     * @return [[@doctodo return_type:cacheDependency]] [[@doctodo return_description:cacheDependency]]
     */
    public static function cacheDependency()
    {
        return Cacher::groupDependency(static::cacheGroupKey());
    }

    /**
     * @inheritdoc
     */
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
     * Get human type.
     *
     * @return [[@doctodo return_type:getHumanType]] [[@doctodo return_description:getHumanType]]
     */
    public function getHumanType()
    {
        $reflector = new ReflectionClass(get_called_class());

        return $reflector->getShortName();
    }

    /**
     * Set tabular.
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setTabularId($value)
    {
        $this->tabularIdHuman = $value;
        $this->_tabularId = static::generateTabularId($value);
    }

    /**
     * Get tabular.
     *
     * @return [[@doctodo return_type:getTabularId]] [[@doctodo return_description:getTabularId]]
     */
    public function getTabularId()
    {
        return $this->_tabularId;
    }

    /**
     * Get tabular prefix.
     *
     * @return [[@doctodo return_type:getTabularPrefix]] [[@doctodo return_description:getTabularPrefix]]
     */
    public function getTabularPrefix()
    {
        if ($this->tabularId === false) {
            return '';
        }

        return '[' . $this->tabularId . ']';
    }

    /**
     * [[@doctodo method_description:generateTabularId]].
     *
     * @param [[@doctodo param_type:id]] $id [[@doctodo param_description:id]]
     *
     * @return [[@doctodo return_type:generateTabularId]] [[@doctodo return_description:generateTabularId]]
     */
    public static function generateTabularId($id)
    {
        if ($id === false) {
            return false;
        }
        if (substr($id, 0, strlen(static::TABULAR_PREFIX)) === static::TABULAR_PREFIX) {
            return $id;
        }

        return static::TABULAR_PREFIX . substr(md5($id), 0, 10);
    }

    /**
     * Get primary tabular.
     *
     * @return [[@doctodo return_type:getPrimaryTabularId]] [[@doctodo return_description:getPrimaryTabularId]]
     */
    public static function getPrimaryTabularId()
    {
        return false;

        return static::generateTabularId(static::FORM_PRIMARY_MODEL);
    }

    /**
     * Get primary model.
     *
     * @param [[@doctodo param_type:models]] $models [[@doctodo param_description:models]]
     *
     * @return [[@doctodo return_type:getPrimaryModel]] [[@doctodo return_description:getPrimaryModel]]
     */
    public static function getPrimaryModel($models)
    {
        if (empty($models)) {
            return false;
        }
        \d($models);
        exit;
        foreach ($models as $tabKey => $model) {
            if ($tabKey === static::getPrimaryTabularId(static::baseClassName())) {
                return $model;
            }
        }

        return false;
    }

    /**
     * [[@doctodo method_description:parseModelAlias]].
     *
     * @param [[@doctodo param_type:alias]] $alias [[@doctodo param_description:alias]]
     *
     * @return [[@doctodo return_type:parseModelAlias]] [[@doctodo return_description:parseModelAlias]]
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
     * Get model alias.
     *
     * @return [[@doctodo return_type:getModelAlias]] [[@doctodo return_description:getModelAlias]]
     */
    public function getModelAlias()
    {
        return static::modelAlias();
    }

    /**
     * [[@doctodo method_description:modelAlias]].
     *
     * @param [[@doctodo param_type:className]] $className [[@doctodo param_description:className]] [optional]
     *
     * @return [[@doctodo return_type:modelAlias]] [[@doctodo return_description:modelAlias]]
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
            return $alias . '\\' . $class->getShortName();
        }

        return $className;
    }

    /**
     * [[@doctodo method_description:clearCache]].
     *
     * @param [[@doctodo param_type:model]] $model [[@doctodo param_description:model]] [optional]
     *
     * @return [[@doctodo return_type:clearCache]] [[@doctodo return_description:clearCache]]
     */
    public static function clearCache($model = null)
    {
        if (is_null($model)) {
            static::$_cache = [];
        } elseif (isset(static::$_cache[$model])) {
            static::$_cache[$model] = [];
        }

        return true;
    }

    /**
     * Get cache size.
     *
     * @return [[@doctodo return_type:getCacheSize]] [[@doctodo return_description:getCacheSize]]
     */
    public function getCacheSize()
    {
        $n = 0;
        foreach (static::$_cache as $model => $cache) {
            $n += count($cache);
        }

        return $n;
    }

    /**
     * Get.
     *
     * @param [[@doctodo param_type:id]] $id          [[@doctodo param_description:id]]
     * @param boolean                    $checkAccess [[@doctodo param_description:checkAccess]] [optional]
     *
     * @return [[@doctodo return_type:get]] [[@doctodo return_description:get]]
     */
    public static function get($id, $checkAccess = true)
    {
        $class = get_called_class();
        $dummy = new $class();

        return static::findOne([$dummy->tableName() . '.' . $dummy->primaryKey()[0] => $id], $checkAccess);
    }

    /**
     * @inheritdoc
     */
    public static function findOne($where, $checkAccess = true)
    {
        return static::_findCache('one', $where, $checkAccess);
    }

    /**
     * @inheritdoc
     */
    public static function findAll($where = false, $checkAccess = true)
    {
        return static::_findCache('all', $where, $checkAccess);
    }

    /**
     * [[@doctodo method_description:findAllCache]].
     *
     * @param boolean $where       [[@doctodo param_description:where]] [optional]
     * @param boolean $checkAccess [[@doctodo param_description:checkAccess]] [optional]
     *
     * @return [[@doctodo return_type:findAllCache]] [[@doctodo return_description:findAllCache]]
     */
    public static function findAllCache($where = false, $checkAccess = true)
    {
        return static::_findCache('all', $where, $checkAccess);
    }

    /**
     * [[@doctodo method_description:_findCache]].
     *
     * @param [[@doctodo param_type:type]] $type        [[@doctodo param_description:type]]
     * @param boolean                      $where       [[@doctodo param_description:where]] [optional]
     * @param boolean                      $checkAccess [[@doctodo param_description:checkAccess]] [optional]
     *
     * @return [[@doctodo return_type:_findCache]] [[@doctodo return_description:_findCache]]
     */
    protected static function _findCache($type, $where = false, $checkAccess = true)
    {
        if (is_array($where)) {
            ksort($where);
        }
        $model = static::className();
        $key = md5(serialize(['type' => $type, 'where' => $where, 'access' => $checkAccess]));
        if (!isset(static::$_cache[$model])) {
            static::$_cache[$model] = [];
        }
        if (!isset(static::$_cache[$model][$key])) {
            $r = static::find();
            if ($where) {
                $r->where($where);
            }
            if (!$checkAccess && $r->hasBehavior('Access')) {
                $r->disableAccessCheck();
            }
            $r = $r->$type();
            if ($r) {
                static::$_cache[$model][$key] = $r;
            } else {
                if ($type === 'one') {
                    return false;
                }

                return [];
            }
        }

        return static::$_cache[$model][$key];
    }

    /**
     * [[@doctodo method_description:tableExists]].
     *
     * @return [[@doctodo return_type:tableExists]] [[@doctodo return_description:tableExists]]
     */
    public static function tableExists()
    {
        try {
            static::getTableSchema();
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Creates an [[ActiveQuery]] instance.
     * This method is called by [[find()]], [[findBySql()]] and [[count()]] to start a SELECT query.
     * You may override this method to return a customized query (e.g. `CustomerQuery` specified
     * written for querying `Customer` purpose.).
     *
     * @return ActiveQuery the newly created [[ActiveQuery]] instance.
     */
    public static function find()
    {
        if (is_null(static::$queryClass)) {
            $queryClass = 'canis\db\ActiveQuery';
        } else {
            $queryClass = static::$queryClass;
        }
        $query = new $queryClass(get_called_class());
        $query->attachBehaviors(static::queryBehaviors());

        return $query;
    }

    /**
     * [[@doctodo method_description:isAccessControlled]].
     *
     * @return [[@doctodo return_type:isAccessControlled]] [[@doctodo return_description:isAccessControlled]]
     */
    public static function isAccessControlled()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'Date' => [
                'class' => 'canis\db\behaviors\Date',
            ],
            'Blame' => [
                'class' => 'canis\db\behaviors\Blame',
            ],
            'Archivable' => [
                'class' => 'canis\db\behaviors\ActiveArchivable',
            ],
        ];
    }

    /**
     * [[@doctodo method_description:queryBehaviors]].
     *
     * @return [[@doctodo return_type:queryBehaviors]] [[@doctodo return_description:queryBehaviors]]
     */
    public static function queryBehaviors()
    {
        return [
            'Archivable' => [
                'class' => 'canis\db\behaviors\QueryArchivable',
            ],
        ];
    }

    /**
     * Get subdescriptor fields.
     *
     * @return [[@doctodo return_type:getSubdescriptorFields]] [[@doctodo return_description:getSubdescriptorFields]]
     */
    public function getSubdescriptorFields()
    {
        return [];
    }

    /**
     * Get descriptor.
     *
     * @return [[@doctodo return_type:getDescriptor]] [[@doctodo return_description:getDescriptor]]
     */
    public function getDescriptor()
    {
        if (isset($this->descriptorField)) {
            return $this->parseDescriptorField($this->descriptorField);
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
     * Get sort options.
     *
     * @return [[@doctodo return_type:getSortOptions]] [[@doctodo return_description:getSortOptions]]
     */
    public function getSortOptions()
    {
        $options = [];
        $descriptorSort = [];
        $modelDescriptorFields = $this->descriptorField;
        if (!is_array($modelDescriptorFields)) {
            $modelDescriptorFields = [$modelDescriptorFields];
        }
        foreach ($modelDescriptorFields as $field) {
            if ($this->hasAttribute($field)) {
                $descriptorSort[$field] = SORT_ASC;
            }
        }
        if (!empty($descriptorSort)) {
            $options[$this->descriptorLabel] = $descriptorSort;
        }
        if (empty($options) && $this->hasAttribute('created')) {
            $options['Created'] = ['created' => SORT_ASC];
        }

        return $options;
    }

    /**
     * Get descriptor default order.
     *
     * @param string  $alias [[@doctodo param_description:alias]] [optional]
     * @param integer $order [[@doctodo param_description:order]] [optional]
     *
     * @return [[@doctodo return_type:getDescriptorDefaultOrder]] [[@doctodo return_description:getDescriptorDefaultOrder]]
     */
    public function getDescriptorDefaultOrder($alias = '{alias}', $order = SORT_ASC)
    {
        $descriptorField = $this->descriptorField;
        if (!is_array($descriptorField)) {
            $descriptorField = [$descriptorField];
        }
        $descriptorField = array_reverse($descriptorField);
        $sortBy = [];
        foreach ($descriptorField as $field) {
            if (!$this->hasAttribute($field)) {
                continue;
            }
            $sortBy[$alias . '.' . $field] = $order;
        }

        return $sortBy;
    }

    /**
     * Get default order.
     *
     * @param string $alias [[@doctodo param_description:alias]] [optional]
     *
     * @return [[@doctodo return_type:getDefaultOrder]] [[@doctodo return_description:getDefaultOrder]]
     */
    public function getDefaultOrder($alias = 't')
    {
        if (is_null($this->_defaultOrder)) {
            $this->_defaultOrder = $this->getDescriptorDefaultOrder('{alias}');
        }
        $sortBy = [];
        foreach ($this->_defaultOrder as $key => $value) {
            $sortBy[strtr($key, ['{alias}' => $alias])] = $value;
        }

        return $sortBy;
    }

    /**
     * [[@doctodo method_description:parseDescriptorField]].
     *
     * @param [[@doctodo param_type:config]] $config [[@doctodo param_description:config]]
     *
     * @return [[@doctodo return_type:parseDescriptorField]] [[@doctodo return_description:parseDescriptorField]]
     */
    protected function parseDescriptorField($config)
    {
        if (is_array($config)) {
            $descriptor = [];
            foreach ($config as $field) {
                if (!empty($this->{$field})) {
                    $descriptor[] = $this->{$field};
                }
            }

            return implode(' ', $descriptor);
        } else {
            return $this->{$config};
        }
    }

    /**
     * Get short descriptor.
     *
     * @return [[@doctodo return_type:getShortDescriptor]] [[@doctodo return_description:getShortDescriptor]]
     */
    public function getShortDescriptor()
    {
        if ($this->shortDescriptorField === false) {
            $value = $this->descriptor;
        } else {
            $value = $this->parseDescriptorField($this->shortDescriptorField);
        }
        if ($this->shortDescriptorLength) {
            if (strlen($value) > $this->shortDescriptorLength) {
                $value = substr($value, 0, $this->shortDescriptorLength) . '…';
            }
        }

        return $value;
    }

    /**
     * [[@doctodo method_description:hasIcon]].
     *
     * @return [[@doctodo return_type:hasIcon]] [[@doctodo return_description:hasIcon]]
     */
    public function hasIcon()
    {
        return false;
    }

    /**
     * Get icon.
     *
     * @return [[@doctodo return_type:getIcon]] [[@doctodo return_description:getIcon]]
     */
    public function getIcon()
    {
        return;
    }

    /**
     * Get primary subdescriptor.
     *
     * @param [[@doctodo param_type:context]] $context [[@doctodo param_description:context]] [optional]
     *
     * @return [[@doctodo return_type:getPrimarySubdescriptor]] [[@doctodo return_description:getPrimarySubdescriptor]]
     */
    public function getPrimarySubdescriptor($context = null)
    {
        $subdescriptor = [];
        foreach ($this->getSubdescriptor($context) as $subValue) {
            if (!empty($subValue)) {
                if (is_array($subValue) && isset($subValue['plain'])) {
                    $subdescriptor[] = $subValue['plain'];
                } elseif (is_array($subValue) && isset($subValue['rich'])) {
                    $subdescriptor[] = strip_tags($subValue['rich']);
                } elseif (is_string($subValue) || is_numeric($subValue)) {
                    $subdescriptor[] = strip_tags($subValue);
                }
            }
        }

        return isset($subdescriptor[0]) ? $subdescriptor[0] : null;
    }

    /**
     * Get subdescriptor.
     *
     * @param [[@doctodo param_type:context]] $context [[@doctodo param_description:context]] [optional]
     *
     * @return [[@doctodo return_type:getSubdescriptor]] [[@doctodo return_description:getSubdescriptor]]
     */
    public function getSubdescriptor($context = null)
    {
        $sub = [];
        foreach ($this->subdescriptorFields as $fieldName => $fieldOptions) {
            if (is_numeric($fieldName)) {
                $fieldName = $fieldOptions;
                $fieldOptions = [];
            }
            $value = $this->getFieldValue($fieldName, $fieldOptions, $context);
            if (!empty($value)) {
                $sub[] = $value;
            }
        }

        return $sub;
    }

    /**
     * [[@doctodo method_description:isForeignField]].
     *
     * @param [[@doctodo param_type:field]] $field [[@doctodo param_description:field]]
     *
     * @return [[@doctodo return_type:isForeignField]] [[@doctodo return_description:isForeignField]]
     */
    public function isForeignField($field)
    {
        return !isset($this->{$field});
    }

    /**
     * Get field value.
     *
     * @param [[@doctodo param_type:field]]   $field     [[@doctodo param_description:field]]
     * @param array                           $options   [[@doctodo param_description:options]] [optional]
     * @param [[@doctodo param_type:context]] $context   [[@doctodo param_description:context]] [optional]
     * @param boolean                         $formatted [[@doctodo param_description:formatted]] [optional]
     *
     * @return [[@doctodo return_type:getFieldValue]] [[@doctodo return_description:getFieldValue]]
     */
    public function getFieldValue($field, $options = [], $context = null, $formatted = true)
    {
        if (is_array($field)) {
            // first with a value is our winner
            foreach ($field as $subfieldName => $subfieldOptions) {
                if (is_numeric($subfieldName)) {
                    $subfieldName = $subfieldOptions;
                    $subfieldOptions = [];
                }
                $value = $this->getFieldValue($subfieldName, array_merge($options, $subfieldOptions), $context);
                if (!empty($value)) {
                    return $value;
                }
            }

            return;
        }
        if ($this->isForeignField($field)) {
            return $this->getForeignFieldValue($field, $options, $context, $formatted);
        } else {
            return $this->getLocalFieldValue($field, $options, $context, $formatted);
        }
    }

    /**
     * Get local field value.
     *
     * @param [[@doctodo param_type:field]]   $field     [[@doctodo param_description:field]]
     * @param array                           $options   [[@doctodo param_description:options]] [optional]
     * @param [[@doctodo param_type:context]] $context   [[@doctodo param_description:context]] [optional]
     * @param boolean                         $formatted [[@doctodo param_description:formatted]] [optional]
     *
     * @return [[@doctodo return_type:getLocalFieldValue]] [[@doctodo return_description:getLocalFieldValue]]
     */
    public function getLocalFieldValue($field, $options = [], $context = null, $formatted = true)
    {
        if (isset($this->{$field})) {
            return $this->{$field};
        }

        return;
    }

    /**
     * Get foreign field value.
     *
     * @param [[@doctodo param_type:field]]   $field     [[@doctodo param_description:field]]
     * @param array                           $options   [[@doctodo param_description:options]] [optional]
     * @param [[@doctodo param_type:context]] $context   [[@doctodo param_description:context]] [optional]
     * @param boolean                         $formatted [[@doctodo param_description:formatted]] [optional]
     *
     * @return [[@doctodo return_type:getForeignFieldValue]] [[@doctodo return_description:getForeignFieldValue]]
     */
    public function getForeignFieldValue($field, $options = [], $context = null, $formatted = true)
    {
        return;
    }

    /**
     * [[@doctodo method_description:checkExistence]].
     *
     * @return [[@doctodo return_type:checkExistence]] [[@doctodo return_description:checkExistence]]
     */
    public function checkExistence()
    {
        if (empty($this->primaryKey)) {
            return false;
        }

        return static::find()->pk($this->primaryKey)->count() > 0;
    }

    /**
     * [[@doctodo method_description:quote]].
     *
     * @param unknown $value
     *
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
        if (is_null($value)) {
            return $value;
        }

        return $this->db->quoteValue($value);
    }

    /**
     * [[@doctodo method_description:save]].
     *
     * @param unknown $runValidation (optional)
     * @param unknown $attributes    (optional)
     *
     * @return unknown
     *
     * @todo see if they added an event in the final version of Yii2
     */
    public function save($runValidation = true, $attributes = null)
    {
        if (parent::save($runValidation, $attributes)) {
            return true;
        } else {
            $event = new ModelEvent();
            $this->trigger(static::EVENT_AFTER_SAVE_FAIL, $event);

            return false;
        }
    }

    /**
     * Get package.
     *
     * @param string $urlAction [[@doctodo param_description:urlAction]] [optional]
     *
     * @return [[@doctodo return_type:getPackage]] [[@doctodo return_description:getPackage]]
     */
    public function getPackage($urlAction = 'view')
    {
        $p = [];
        $p['id'] = $this->primaryKey;
        $p['descriptor'] = $this->descriptor;
        $p['url'] = false;
        if (method_exists($this, 'getUrl')) {
            $p['url'] = Url::to($this->getUrl($urlAction));
        }

        return $p;
    }
}
