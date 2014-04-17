<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\models;

/**
 * Acl is the model class for table "acl".
 *
 *
 * @property string $id
 * @property string $acl_role_id
 * @property string $accessing_object_id
 * @property string $controlled_object_id
 * @property string $aca_id
 * @property boolean $access
 * @property string $created
 * @property string $modified
 *
 * @property \yii\db\ActiveRelation $accessingObject This property is read-only.
 * @property \yii\db\ActiveRelation $aclRole This property is read-only.
 * @property \yii\db\ActiveRelation $controlledObject This property is read-only.
 *
 * @property AclRole $aclRole
 * @property Registry $accessingObject
 * @property Registry $controlledObject
 */
 * @author Jacob Morrison <email@ofjacob.com>
class Acl extends \infinite\db\ActiveRecord
{
    public static $registryCache = false;
    public static $relationCache = false;
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
        return 'acl';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['acl_role_id'], 'integer'],
            [['accessing_object_id'], 'required'],
            [['access'], 'integer'],
            [['created', 'modified'], 'safe'],
            [['accessing_object_id', 'controlled_object_id', 'aca_id'], 'string', 'max' => 36]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'acl_role_id' => 'Acl Role ID',
            'accessing_object_id' => 'Accessing Object ID',
            'controlled_object_id' => 'Controlled Object ID',
            'aca_id' => 'Aca ID',
            'access' => 'Access',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getAclRole()
    {
        return $this->hasOne('AclRole', ['id' => 'acl_role_id']);
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getAccessingObject()
    {
        return $this->hasOne('Registry', ['id' => 'accessing_object_id']);
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getControlledObject()
    {
        return $this->hasOne('Registry', ['id' => 'controlled_object_id']);
    }
}
