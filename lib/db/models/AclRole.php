<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\models;

use Yii;

/**
 * AclRole is the model class for table "acl_role".
 *
 * @property string $id
 * @property string $accessing_object_id
 * @property string $controlled_object_id
 * @property string $role_id
 * @property string $created
 * @property string $modified
 * @property Acl[] $acls
 * @property Registry $accessingObject
 * @property Registry $controlledObject
 * @property Registry $role
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class AclRole extends \canis\db\ActiveRecord
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
        return 'acl_role';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['accessing_object_id', 'controlled_object_id'], 'required'],
            [['created', 'modified'], 'safe'],
            [['accessing_object_id', 'controlled_object_id', 'role_id'], 'string', 'max' => 36],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'accessing_object_id' => 'Accessing Object ID',
            'controlled_object_id' => 'Controlled Object ID',
            'role_id' => 'Role ID',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }

    /**
     * Get acls.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getAcls()
    {
        return $this->hasMany('Acl', ['acl_role_id' => 'id']);
    }

    /**
     * Get accessing object.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getAccessingObject()
    {
        return $this->hasOne(Yii::$app->classes['Registry'], ['id' => 'accessing_object_id']);
    }

    /**
     * Get controlled object.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getControlledObject()
    {
        return $this->hasOne(Yii::$app->classes['Registry'], ['id' => 'controlled_object_id']);
    }

    /**
     * Get role.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getRole()
    {
        return $this->hasOne(Yii::$app->classes['Role'], ['id' => 'role_id']);
    }
}
