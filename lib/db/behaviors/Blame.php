<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors;

use Yii;

/**
 * Blame [[@doctodo class_description:teal\db\behaviors\Blame]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Blame extends \teal\db\behaviors\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:deletedField]] [[@doctodo var_description:deletedField]]
     */
    public $deletedField = 'deleted';
    /**
     * @var [[@doctodo var_type:deletedByField]] [[@doctodo var_description:deletedByField]]
     */
    public $deletedByField = 'deleted_user_id';

    /**
     * @var [[@doctodo var_type:createdField]] [[@doctodo var_description:createdField]]
     */
    public $createdField = 'created';
    /**
     * @var [[@doctodo var_type:createdByField]] [[@doctodo var_description:createdByField]]
     */
    public $createdByField = 'created_user_id';

    /**
     * @var [[@doctodo var_type:modifiedField]] [[@doctodo var_description:modifiedField]]
     */
    public $modifiedField = 'modified';
    /**
     * @var [[@doctodo var_type:modifiedByField]] [[@doctodo var_description:modifiedByField]]
     */
    public $modifiedByField = 'modified_user_id';

    /**
     * @var [[@doctodo var_type:databaseTimeFormat]] [[@doctodo var_description:databaseTimeFormat]]
     */
    public $databaseTimeFormat = 'Y-m-d H:i:s';

    /**
     * @var [[@doctodo var_type:_userID]] [[@doctodo var_description:_userID]]
     */
    public static $_userID;
    /**
     * @var [[@doctodo var_type:_fields]] [[@doctodo var_description:_fields]]
     */
    protected static $_fields = [];

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \teal\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeInsert',
            \teal\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeUpdate',
        ];
    }

    /**
     * Get fields.
     *
     * @return [[@doctodo return_type:getFields]] [[@doctodo return_description:getFields]]
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

    /**
     * [[@doctodo method_description:isActuallyDirty]].
     *
     * @return [[@doctodo return_type:isActuallyDirty]] [[@doctodo return_description:isActuallyDirty]]
     */
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
     * [[@doctodo method_description:beforeInsert]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeInsert]] [[@doctodo return_description:beforeInsert]]
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
     * [[@doctodo method_description:beforeUpdate]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeUpdate]] [[@doctodo return_description:beforeUpdate]]
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
     * [[@doctodo method_description:_getUserId]].
     *
     * @return [[@doctodo return_type:_getUserId]] [[@doctodo return_description:_getUserId]]
     */
    protected static function _getUserId()
    {
        if (is_null(self::$_userID)) {
            self::$_userID = null;
            if (isset(Yii::$app->user) and !empty(Yii::$app->user->id)) {
                self::$_userID = Yii::$app->user->id;
            }
        }

        return self::$_userID;
    }
}
