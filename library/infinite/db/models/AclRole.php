<?php

namespace infinite\db\models;

/**
 * This is the model class for table "acl_role".
 *
 * @property string $id
 * @property string $accessing_object_id
 * @property string $controlled_object_id
 * @property string $role_id
 * @property string $created
 * @property string $modified
 *
 * @property Acl[] $acls
 * @property Registry $accessingObject
 * @property Registry $controlledObject
 * @property Registry $role
 */
class AclRole extends \infinite\db\ActiveRecord
{
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
			['accessing_object_id, controlled_object_id, role_id', 'required'],
			['created, modified', 'safe'],
			['accessing_object_id, controlled_object_id, role_id', 'string', 'max' => 36]
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
	 * @return \yii\db\ActiveRelation
	 */
	public function getAcls()
	{
		return $this->hasMany('Acl', ['acl_role_id' => 'id']);
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

	/**
	 * @return \yii\db\ActiveRelation
	 */
	public function getRole()
	{
		return $this->hasOne('Registry', ['id' => 'role_id']);
	}
}
