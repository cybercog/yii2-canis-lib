<?php

namespace infinite\db\models;

/**
 * This is the model class for table "acl".
 *
 * @property string $id
 * @property string $acl_role_id
 * @property string $accessing_object_id
 * @property string $controlled_object_id
 * @property string $aca_id
 * @property string $object_model
 * @property boolean $access
 * @property string $created
 * @property string $modified
 *
 * @property AclRole $aclRole
 * @property Registry $accessingObject
 * @property Registry $controlledObject
 */
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
			[['accessing_object_id', 'controlled_object_id', 'aca_id'], 'string', 'max' => 36],
			[['object_model'], 'string', 'max' => 100]
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
			'object_model' => 'Object Model',
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
