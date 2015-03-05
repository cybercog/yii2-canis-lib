<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\models;

use Yii;

/**
 * Relation is the model class for table "relation".
 *
 * @property string $id
 * @property string $parent_object_id
 * @property string $child_object_id
 * @property string $start
 * @property string $end
 * @property boolean $active
 * @property boolean $primary_parent
 * @property boolean $primary_child
 * @property string $special
 * @property string $created
 * @property string $modified
 * @property Registry $childObject
 * @property Registry $parentObject
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Relation extends \infinite\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static $registryCache = false;
    /**
     * @inheritdoc
     */
    public static $relationCache = false;
    /**
     * @var [[@doctodo var_type:_enableAuditLogging]] [[@doctodo var_description:_enableAuditLogging]]
     */
    protected $_enableAuditLogging = true;
    /**
     * @var [[@doctodo var_type:_parentModel]] [[@doctodo var_description:_parentModel]]
     */
    protected $_parentModel;
    /**
     * @var [[@doctodo var_type:_dependencies]] [[@doctodo var_description:_dependencies]]
     */
    protected $_dependencies;
    /**
     * @var [[@doctodo var_type:_newDependencies]] [[@doctodo var_description:_newDependencies]]
     */
    protected $_newDependencies = [];
    /**
     * @var [[@doctodo var_type:_dirtyAttributes]] [[@doctodo var_description:_dirtyAttributes]]
     */
    protected $_dirtyAttributes = [];

    /*
     */
    /**
     * @var [[@doctodo var_type:_modelRegistry]] [[@doctodo var_description:_modelRegistry]]
     */
    public static $_modelRegistry = [];
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_VALIDATE, [$this, 'beforeValidateRelation']);
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'afterSaveRelation']);
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'afterInsertRelation']);
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'afterSaveRelation']);
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'afterUpdateRelation']);
        $this->on(self::EVENT_BEFORE_UPDATE, [$this, 'beforeUpdateRelation']);
        $this->on(self::EVENT_BEFORE_DELETE, [$this, 'beforeDeleteRelation']);
        $this->on(self::EVENT_AFTER_DELETE, [$this, 'afterDeleteRelation']);
    }

    /**
     * @inheritdoc
     */
    public static function get($id, $checkAccess = false)
    {
        if (!isset(self::$_modelRegistry[$id])) {
            self::$_modelRegistry[$id] = parent::get($id, false);
        }

        return self::$_modelRegistry[$id];
    }

    /**
     * [[@doctodo method_description:registerModel]].
     *
     * @return [[@doctodo return_type:registerModel]] [[@doctodo return_description:registerModel]]
     */
    public static function registerModel($model)
    {
        if (!is_object($model)) {
            $modelAttributes = $model;
            $model = new static();
            self::populateRecord($model, $modelAttributes);
        }
        if (!empty($model->primaryKey)) {
            self::$_modelRegistry[$model->primaryKey] = $model;

            return $model;
        }

        return false;
    }

    /**
     * Get register model.
     *
     * @return [[@doctodo return_type:getRegisterModel]] [[@doctodo return_description:getRegisterModel]]
     */
    public static function getRegisterModel($model)
    {
        if (is_object($model)) {
            return $model;
        }
        $actualModel = self::get($model['id']);
        if (!$actualModel) {
            return self::registerModel($model);
        }

        return $actualModel;
    }

    /**
     * @inheritdoc
     */
    public function formName()
    {
        $parentFormName = parent::formName();
        if (isset($this->_parentModel)) {
            $parentModelClass = get_class($this->_parentModel);

            return $this->_parentModel->formName() . $this->_parentModel->tabularPrefix . '[relations]';
        }

        return $parentFormName;
    }

    /**
     * Set parent model.
     *
     * @param boolean $clearTabularPrefix [[@doctodo param_description:clearTabularPrefix]] [optional]
     */
    public function setParentModel($parentModel, $clearTabularPrefix = false)
    {
        $this->_parentModel = $parentModel;
        if ($clearTabularPrefix) {
            $this->_tabularId = false;
        }
    }

    /**
     * @inheritdoc
     */
    public static function isAccessControlled()
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'relation';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['start', 'end', 'created', 'modified'], 'safe'],
            [['active', 'primary_parent', 'primary_child'], 'boolean'],
            [['parent_object_id', 'child_object_id'], 'string', 'max' => 36],
            [['special'], 'string', 'max' => 100],
        ];
    }

    /**
     * [[@doctodo method_description:beforeValidateRelation]].
     *
     * @return [[@doctodo return_type:beforeValidateRelation]] [[@doctodo return_description:beforeValidateRelation]]
     */
    public function beforeValidateRelation($event)
    {
        if (empty($this->start)) {
            $this->start = null;
        } else {
            $this->start = date("Y-m-d", strtotime($this->start . " 12:00"));
        }
        if (empty($this->end)) {
            $this->end = null;
        } else {
            $this->end = date("Y-m-d", strtotime($this->end . " 12:00"));
        }

        return true;
    }

    /**
     * [[@doctodo method_description:afterInsertRelation]].
     */
    public function afterInsertRelation($event)
    {
        if ($this->_enableAuditLogging) {
            $parentObject = $this->parentObject;
            if (!empty($parentObject) && $parentObject->getBehavior('Relatable') !== null) {
                $parentObject->registerCreateRelationAuditEvent($this);
            }
        }
    }

    /**
     * [[@doctodo method_description:afterSaveRelation]].
     *
     * @return [[@doctodo return_type:afterSaveRelation]] [[@doctodo return_description:afterSaveRelation]]
     */
    public function afterSaveRelation($event)
    {
        $relationDependencyClass = Yii::$app->classes['RelationDependency'];
        foreach ($this->newDependencies as $dependency) {
            if (!isset($this->dependencies[$dependency])) {
                $relationDependency = new $relationDependencyClass();
                $relationDependency->attributes = ['parent_relation_id' => $this->primaryKey, 'child_relation_id' => $dependency];
                if (!$relationDependency->save()) {
                    $event->handled = false;
                } else {
                    $this->_dependencies[$dependency] = $relationDependency;
                }
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:beforeUpdateRelation]].
     *
     * @return [[@doctodo return_type:beforeUpdateRelation]] [[@doctodo return_description:beforeUpdateRelation]]
     */
    public function beforeUpdateRelation($event)
    {
        $this->_dirtyAttributes = $this->getDirtyAttributes();

        return true;
    }

    /**
     * [[@doctodo method_description:afterUpdateRelation]].
     *
     * @return [[@doctodo return_type:afterUpdateRelation]] [[@doctodo return_description:afterUpdateRelation]]
     */
    public function afterUpdateRelation($event)
    {
        $dirty = $this->_dirtyAttributes;
        $this->_dirtyAttributes = [];
        unset($dirty['parent_object_id'], $dirty['child_object_id'], $dirty['id'], $dirty['created'], $dirty['modified']);
        if (empty($dirty)) {
            return true;
        }
        foreach ($this->dependencies as $dependency) {
            $dependency->childRelation->attributes = $dirty;
            if (!$dependency->childRelation->save()) {
                $event->handled = false;
            }
        }

        if ($this->_enableAuditLogging) {
            $parentObject = $this->parentObject;
            if (!empty($parentObject) && $parentObject->getBehavior('Relatable') !== null) {
                $parentObject->registerUpdateRelationAuditEvent($this);
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:suppressAudit]].
     *
     * @return [[@doctodo return_type:suppressAudit]] [[@doctodo return_description:suppressAudit]]
     */
    public function suppressAudit()
    {
        $this->_enableAuditLogging = false;

        return $this;
    }

    /**
     * [[@doctodo method_description:enableLogging]].
     *
     * @return [[@doctodo return_type:enableLogging]] [[@doctodo return_description:enableLogging]]
     */
    public function enableLogging()
    {
        $this->_enableAuditLogging = true;

        return $this;
    }

    /**
     * [[@doctodo method_description:beforeDeleteRelation]].
     *
     * @return [[@doctodo return_type:beforeDeleteRelation]] [[@doctodo return_description:beforeDeleteRelation]]
     */
    public function beforeDeleteRelation($event)
    {
        foreach ($this->dependencies as $dependency) {
            $dependency->childRelation->delete();
        }

        return true;
    }

    /**
     * [[@doctodo method_description:afterDeleteRelation]].
     *
     * @return [[@doctodo return_type:afterDeleteRelation]] [[@doctodo return_description:afterDeleteRelation]]
     */
    public function afterDeleteRelation($event)
    {
        if ($this->_enableAuditLogging) {
            $parentObject = $this->parentObject;
            if (!empty($parentObject) && $parentObject->getBehavior('Relatable') !== null) {
                $parentObject->registerDeleteRelationAuditEvent($this);
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_object_id' => 'Parent Object ID',
            'child_object_id' => 'Child Object ID',
            'start' => 'Start',
            'end' => 'End',
            'active' => 'Active',
            'primary_parent' => 'Primary Parent',
            'primary_child' => 'Primary Child',
            'special' => 'Special',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }

    /**
     * Get child object.
     *
     * @param boolean $checkAccess [[@doctodo param_description:checkAccess]] [optional]
     *
     * @return [[@doctodo return_type:getChildObject]] [[@doctodo return_description:getChildObject]]
     */
    public function getChildObject($checkAccess = true)
    {
        $registryClass = Yii::$app->classes['Registry'];
        if (empty($this->child_object_id)) {
            return false;
        }

        return $registryClass::getObject($this->child_object_id, $checkAccess);
    }

    /**
     * Get parent object.
     *
     * @param boolean $checkAccess [[@doctodo param_description:checkAccess]] [optional]
     *
     * @return [[@doctodo return_type:getParentObject]] [[@doctodo return_description:getParentObject]]
     */
    public function getParentObject($checkAccess = true)
    {
        $registryClass = Yii::$app->classes['Registry'];
        if (empty($this->parent_object_id)) {
            return false;
        }

        return $registryClass::getObject($this->parent_object_id, $checkAccess);
    }

    /**
     * [[@doctodo method_description:endRelationship]].
     *
     * @return [[@doctodo return_type:endRelationship]] [[@doctodo return_description:endRelationship]]
     */
    public function endRelationship()
    {
        $this->end = date("Y-m-d", strtotime("-1 day"));

        if ($this->_enableAuditLogging) {
            $parentObject = $this->parentObject;
            if (!empty($parentObject) && $parentObject->getBehavior('Relatable') !== null) {
                $parentObject->registerEndRelationAuditEvent($this);
            }
        }

        return $this->save();
    }

    /**
     * Get is active.
     *
     * @return [[@doctodo return_type:getIsActive]] [[@doctodo return_description:getIsActive]]
     */
    public function getIsActive()
    {
        if (empty($this->active)) {
            return false;
        }

        $today = strtotime(date("Y-m-d") . " 12:00:00");
        if (!empty($this->start)) {
            $start = strtotime($this->start . " 12:00:00");
            if ($start > $today) {
                return false;
            }
        }

        if (!empty($this->end)) {
            $end = strtotime($this->end . " 12:00:00");
            if ($end < $today) {
                return false;
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:addDependency]].
     */
    public function addDependency($dependency)
    {
        $this->_newDependencies[] = $dependency;
    }

    /**
     * [[@doctodo method_description:clearDependencies]].
     */
    public function clearDependencies()
    {
        $this->_newDependencies = [];
    }

    /**
     * [[@doctodo method_description:addDependencies]].
     */
    public function addDependencies($dependencies)
    {
        foreach ($dependencies as $dependency) {
            $this->_newDependencies[] = $dependency;
        }
        $this->_newDependencies = array_unique($this->_newDependencies);
    }

    /**
     * Get new dependencies.
     *
     * @return [[@doctodo return_type:getNewDependencies]] [[@doctodo return_description:getNewDependencies]]
     */
    public function getNewDependencies()
    {
        return $this->_newDependencies;
    }

    /**
     * Get dependencies.
     *
     * @return [[@doctodo return_type:getDependencies]] [[@doctodo return_description:getDependencies]]
     */
    public function getDependencies()
    {
        if (!isset($this->_dependencies)) {
            $this->_dependencies = [];
            $relationDependencyClass = Yii::$app->classes['RelationDependency'];
            $all = $relationDependencyClass::findAll(['parent_relation_id' => $this->primaryKey]);
            foreach ($all as $dependency) {
                $this->_dependencies[$dependency->child_relation_id] = $dependency;
            }
        }

        return $this->_dependencies;
    }
}
