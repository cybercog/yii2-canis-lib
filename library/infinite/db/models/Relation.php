<?php
/**
 * @link http://www.infinitecascade.com/
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
 *
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
    /**
     * @var __var__callCache_type__ __var__callCache_description__
     */
    static $_callCache = [];
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_VALIDATE, [$this, 'beforeValidateRelation']);
        $this->on(self::EVENT_AFTER_INSERT, [$this, 'afterSaveRelation']);
        $this->on(self::EVENT_AFTER_UPDATE, [$this, 'afterSaveRelation']);
        $this->on(self::EVENT_AFTER_DELETE, [$this, 'afterDeleteRelation']);
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
            [['special'], 'string', 'max' => 100]
        ];
    }

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
     * __method_afterSaveRelation_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_afterSaveRelation_type__ __return_afterSaveRelation_description__
     */
    public function afterSaveRelation($event)
    {
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


    /**
     * __method_afterDeleteRelation_description__
     * @param __param_event_type__ $event __param_event_description__
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
     * Get child object
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
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
     * Get parent object
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
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
     * __method_endRelationship_description__
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
     * Get is active
     * @return __return_getIsActive_type__ __return_getIsActive_description__
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
}
