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
**/
class Blame extends \infinite\db\behaviors\ActiveRecord
{
    public $deletedField = 'deleted';
    public $deletedByField = 'deleted_user_id';

    public $createdField = 'created';
    public $createdByField = 'created_user_id';

    public $modifiedField = 'modified';
    public $modifiedByField = 'modified_user_id';

    public $databaseTimeFormat = 'Y-m-d H:i:s';

    public static $_userID;
    protected $_fields;

    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
        ];
    }

    public function getFields()
    {
        if (is_null($this->_fields)) {
            $this->_fields = [];
            $_f = ['deletedField', 'deletedByField', 'createdField', 'createdByField', 'modifiedField', 'modifiedByField'];
            $ownerClass = get_class($this->owner);
            $schema = $ownerClass::getTableSchema();
            foreach ($_f as $field) {
                if (isset($schema->columns[$this->{$field}])) {
                    $this->_fields[$field] = $this->{$field};
                }
            }
        }

        return $this->_fields;
    }

    public function beforeSave($event)
    {
        $fields = $this->fields;
        $nowDate = date($this->databaseTimeFormat);

        if ($this->owner->isNewRecord) {
            if (isset($this->fields['createdField']) && !$this->owner->isAttributeChanged($this->fields['createdField'])) {
                $this->owner->{$this->fields['createdField']} = $nowDate;
            }
            if (isset($this->fields['createdByField']) && !$this->owner->isAttributeChanged($this->fields['createdByField'])) {
                $this->owner->{$this->fields['createdByField']} = self::_getUserId();
            }
        }

        if (isset($this->fields['modifiedField']) && !$this->owner->isAttributeChanged($this->fields['modifiedField'])) {
            $this->owner->{$this->fields['modifiedField']} = $nowDate;
        }
        if (isset($this->fields['modifiedByField']) && !$this->owner->isAttributeChanged($this->fields['modifiedByField'])) {
            $this->owner->{$this->fields['modifiedByField']} = self::_getUserId();
        }
    }

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
