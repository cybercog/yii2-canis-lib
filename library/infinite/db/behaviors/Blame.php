<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;

/**
 * Blame [@doctodo write class description for Blame]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Blame extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var __var_deletedField_type__ __var_deletedField_description__
     */
    public $deletedField = 'deleted';
    /**
     * @var __var_deletedByField_type__ __var_deletedByField_description__
     */
    public $deletedByField = 'deleted_user_id';

    /**
     * @var __var_createdField_type__ __var_createdField_description__
     */
    public $createdField = 'created';
    /**
     * @var __var_createdByField_type__ __var_createdByField_description__
     */
    public $createdByField = 'created_user_id';

    /**
     * @var __var_modifiedField_type__ __var_modifiedField_description__
     */
    public $modifiedField = 'modified';
    /**
     * @var __var_modifiedByField_type__ __var_modifiedByField_description__
     */
    public $modifiedByField = 'modified_user_id';

    /**
     * @var __var_databaseTimeFormat_type__ __var_databaseTimeFormat_description__
     */
    public $databaseTimeFormat = 'Y-m-d H:i:s';

    /**
     * @var __var__userID_type__ __var__userID_description__
     */
    public static $_userID;
    /**
     * @var __var__fields_type__ __var__fields_description__
     */
    protected static $_fields = [];

    /**
    * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
        ];
    }

    /**
     * Get fields
     * @return __return_getFields_type__ __return_getFields_description__
     */
    public function getFields()
    {
        $ownerClass = get_class($this->owner);
        $ownerTable = $ownerClass::tableName();
        if (!isset(self::$_fields[$ownerTable])) {
            self::$_fields[$ownerTable] = [];
            $_f = ['deletedField', 'deletedByField', 'createdField', 'createdByField', 'modifiedField', 'modifiedByField'];
            $schema = $ownerClass::getTableSchema();
            foreach ($_f as $field) {
                if (isset($schema->columns[$this->{$field}])) {
                    self::$_fields[$ownerTable][$field] = $this->{$field};
                }
            }
        }

        return self::$_fields[$ownerTable];
    }

    public function isActuallyDirty()
    {
        $values = $this->owner->getDirtyAttributes();
        if (isset($this->fields['modifiedField'])) {
            unset($values[$this->fields['modifiedField']]);
        }
        if (isset($this->fields['modifiedByField'])) {
            unset($values[$this->fields['modifiedByField']]);
        }
        if (isset($this->fields['createdField'])) {
            unset($values[$this->fields['createdField']]);
        }
        if (isset($this->fields['createdByField'])) {
            unset($values[$this->fields['createdByField']]);
        }
        return !empty($values);
    }
    /**
     * __method_beforeSave_description__
     * @param __param_event_type__ $event __param_event_description__
     */
    public function beforeInsert($event)
    {
        $fields = $this->fields;
        $nowDate = date($this->databaseTimeFormat);
        if (!$this->isActuallyDirty()) {
            return;
        }

        if (isset($this->fields['createdField']) && !$this->owner->isAttributeChanged($this->fields['createdField'])) {
            $this->owner->{$this->fields['createdField']} = $nowDate;
        }
        if (isset($this->fields['createdByField']) && !$this->owner->isAttributeChanged($this->fields['createdByField'])) {
            $this->owner->{$this->fields['createdByField']} = self::_getUserId();
        }

        if (isset($this->fields['modifiedField']) && !$this->owner->isAttributeChanged($this->fields['modifiedField'])) {
            $this->owner->{$this->fields['modifiedField']} = $nowDate;
        }
        if (isset($this->fields['modifiedByField']) && !$this->owner->isAttributeChanged($this->fields['modifiedByField'])) {
            $this->owner->{$this->fields['modifiedByField']} = self::_getUserId();
        }
    }

    /**
     * __method_beforeSave_description__
     * @param __param_event_type__ $event __param_event_description__
     */
    public function beforeUpdate($event)
    {
        $fields = $this->fields;
        $nowDate = date($this->databaseTimeFormat);
        if (!$this->isActuallyDirty()) {
            return;
        }

        if (isset($this->fields['modifiedField']) && !$this->owner->isAttributeChanged($this->fields['modifiedField'])) {
            $this->owner->{$this->fields['modifiedField']} = $nowDate;
        }
        if (isset($this->fields['modifiedByField']) && !$this->owner->isAttributeChanged($this->fields['modifiedByField'])) {
            $this->owner->{$this->fields['modifiedByField']} = self::_getUserId();
        }
    }

    /**
     * __method__getUserId_description__
     * @return __return__getUserId_type__ __return__getUserId_description__
     */
    protected static function _getUserId()
    {
        if (is_null(self::$_userID)) {
            self::$_userID = null;
            if (isset(Yii::$app->user) AND !empty(Yii::$app->user->id)) {
                self::$_userID = Yii::$app->user->id;
            }
        }

        return self::$_userID;
    }
}
