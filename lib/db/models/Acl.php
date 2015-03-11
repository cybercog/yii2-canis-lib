<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\models;

/**
 * Acl is the model class for table "acl".
 *
 * @property string $id
 * @property string $acl_role_id
 * @property string $accessing_object_id
 * @property string $controlled_object_id
 * @property string $aca_id
 * @property boolean $access
 * @property string $created
 * @property string $modified
 * @property AclRole $aclRole
 * @property Registry $accessingObject
 * @property Registry $controlledObject
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Acl extends \teal\db\ActiveRecord
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
            [['accessing_object_id', 'controlled_object_id', 'aca_id'], 'string', 'max' => 36],
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
     * Get acl role.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getAclRole()
    {
        return $this->hasOne('AclRole', ['id' => 'acl_role_id']);
    }

    /**
     * Get accessing object.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getAccessingObject()
    {
        return $this->hasOne('Registry', ['id' => 'accessing_object_id']);
    }

    /**
     * Get controlled object.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getControlledObject()
    {
        return $this->hasOne('Registry', ['id' => 'controlled_object_id']);
    }
}
