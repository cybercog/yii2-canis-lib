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
    protected $_enableAuditLogging = true;
    protected $_parentModel;
    protected $_dependencies;
    protected $_newDependencies = [];
    protected $_dirtyAttributes = [];

    /*
     * @var __var__callCache_type__ __var__callCache_description__
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

    public static function get($id, $checkAccess = false)
    {
        if (!isset(self::$_modelRegistry[$id])) {
            self::$_modelRegistry[$id] = parent::get($id, false);
        }

        return self::$_modelRegistry[$id];
    }

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

    public function formName()
    {
        $parentFormName = parent::formName();
        if (isset($this->_parentModel)) {
            $parentModelClass = get_class($this->_parentModel);

            return $this->_parentModel->formName().$this->_parentModel->tabularPrefix.'[relations]';
        }

        return $parentFormName;
    }

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

    public function beforeValidateRelation($event)
    {
        if (empty($this->start)) {
            $this->start = null;
        } else {
            $this->start = date("Y-m-d", strtotime($this->start." 12:00"));
        }
        if (empty($this->end)) {
            $this->end = null;
        } else {
            $this->end = date("Y-m-d", strtotime($this->end." 12:00"));
        }

        return true;
    }

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
     * __method_afterSaveRelation_description__.
     *
     * @param __param_event_type__ $event __param_event_description__
     *
     * @return __return_afterSaveRelation_type__ __return_afterSaveRelation_description__
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

    public function beforeUpdateRelation($event)
    {
        $this->_dirtyAttributes = $this->getDirtyAttributes();

        return true;
    }

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

    public function suppressAudit()
    {
        $this->_enableAuditLogging = false;

        return $this;
    }

    public function enableLogging()
    {
        $this->_enableAuditLogging = true;

        return $this;
    }

    public function beforeDeleteRelation($event)
    {
        foreach ($this->dependencies as $dependency) {
            $dependency->childRelation->delete();
        }

        return true;
    }

    /**
     * __method_afterDeleteRelation_description__.
     *
     * @param __param_event_type__ $event __param_event_description__
     *
     * @return __return_afterDeleteRelation_type__ __return_afterDeleteRelation_description__
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
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
     *
     * @return __return_getChildObject_type__ __return_getChildObject_description__
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
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
     *
     * @return __return_getParentObject_type__ __return_getParentObject_description__
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
     * __method_endRelationship_description__.
     *
     * @return __return_endRelationship_type__ __return_endRelationship_description__
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
     * @return __return_getIsActive_type__ __return_getIsActive_description__
     */
    public function getIsActive()
    {
        if (empty($this->active)) {
            return false;
        }

        $today = strtotime(date("Y-m-d")." 12:00:00");
        if (!empty($this->start)) {
            $start = strtotime($this->start." 12:00:00");
            if ($start > $today) {
                return false;
            }
        }

        if (!empty($this->end)) {
            $end = strtotime($this->end." 12:00:00");
            if ($end < $today) {
                return false;
            }
        }

        return true;
    }

    public function addDependency($dependency)
    {
        $this->_newDependencies[] = $dependency;
    }

    public function clearDependencies()
    {
        $this->_newDependencies = [];
    }

    public function addDependencies($dependencies)
    {
        foreach ($dependencies as $dependency) {
            $this->_newDependencies[] = $dependency;
        }
        $this->_newDependencies = array_unique($this->_newDependencies);
    }

    public function getNewDependencies()
    {
        return $this->_newDependencies;
    }

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
