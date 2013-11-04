<?php

namespace infinite\db\models;

/**
 * This is the model class for table "registry".
 *
 * @property string $id
 * @property string $object_model
 * @property string $created
 *
 * @property Aca $aca
 * @property Acl[] $acls
 * @property Acl[] $acls0
 * @property AclRole[] $aclRoles
 * @property AclRole[] $aclRoles0
 * @property AclRole[] $aclRoles1
 * @property Group $group
 * @property Identity $identity
 * @property IdentityProvider $identityProvider
 * @property Relation[] $relations
 * @property Relation[] $relations0
 * @property Role $role
 */
class Registry extends \infinite\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'registry';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['id', 'required'],
			['created', 'safe'],
			['id', 'string', 'max' => 36],
			['object_model', 'string', 'max' => 100]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'object_model' => 'Object Model',
			'created' => 'Created',
		];
	}

	/**
	 *
	 *
	 * @param unknown $id
	 * @return unknown
	 */
	public static function getObject($id, $disableAccess = false) {
		$requestKey = md5(serialize(array(__FUNCTION__, func_get_args())));
		$classKey = self::className();
		if (!isset(self::$_cache[$classKey])) {
			self::$_cache[$classKey] = [];
		}
		if (!isset(self::$_cache[$classKey][$requestKey])) {
			self::$_cache[$classKey][$requestKey] = false;
			$registry = self::get($id);
			if (empty($registry)) { return false; }
			$model = $registry->object_model;
			$object = $model::find();
			if ($disableAccess && $object->hasBehavior('Access')) {
				$object->disableAccess();
			}
			$object = $object->where(['id' => $registry->primaryKey])->one();
			self::$_cache[$classKey][$requestKey] = $object;
		}
		return self::$_cache[$classKey][$requestKey];
	}

}
