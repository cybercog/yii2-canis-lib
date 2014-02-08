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
    public static $registryCache = false;
    public static $relationCache = false;
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
			[['id'], 'required'],
			[['created'], 'safe'],
			[['id'], 'string', 'max' => 36],
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
			'object_model' => 'Object Model',
			'created' => 'Created',
		];
	}

	public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [
                'Relatable' => 'infinite\db\behaviors\Relatable',
            ]
        );
    }
    
	/**
	 *
	 *
	 * @param unknown $id
	 * @return unknown
	 */
	public static function getObject($id, $checkAccess = true) {
		$requestKey = md5(json_encode(['object', $id, $checkAccess]));
		$classKey = self::className();
		if (!isset(self::$_cache[$classKey])) {
			self::$_cache[$classKey] = [];
		}
		if (!isset(self::$_cache[$classKey][$requestKey])) {
			self::$_cache[$classKey][$requestKey] = false;
			$registry = self::get($id, false);
			if (empty($registry)) { return false; }
			$model = self::parseModelAlias($registry->object_model);
			$object = $model::find();
			if (!$checkAccess) {
				$object->disableAccessCheck();
			}
			$object = $object->pk($registry->primaryKey)->one();
			self::$_cache[$classKey][$requestKey] = $object;
		}
		return self::$_cache[$classKey][$requestKey];
	}

	public static function registerObject($object)
	{
		$requestKey = md5(json_encode(['object', $object->primaryKey, true]));
		$classKey = self::className();
		if (!isset(self::$_cache[$classKey])) {
			self::$_cache[$classKey] = [];
		}
		if (empty(self::$_cache[$classKey][$requestKey])) {
			self::$_cache[$classKey][$requestKey] = $object;
		}
	}

}
