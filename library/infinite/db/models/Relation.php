<?php

namespace infinite\db\models;

/**
 * This is the model class for table "relation".
 *
 * @property string $id
 * @property string $parent_object_id
 * @property string $child_object_id
 * @property string $start
 * @property string $end
 * @property boolean $active
 * @property boolean $primary
 * @property string $special
 * @property string $created
 * @property string $modified
 *
 * @property Registry $childObject
 * @property Registry $parentObject
 */
class Relation extends \infinite\db\ActiveRecord
{
    public static $registryCache = false;
    public static $relationCache = false;
	public $registryClass = 'infinite\\models\\Registry';
	static $_callCache = [];
	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		$this->on(self::EVENT_AFTER_INSERT, [$this, 'afterSaveRelation']);
		$this->on(self::EVENT_AFTER_UPDATE, [$this, 'afterSaveRelation']);
	}

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
		return 'relation';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['start', 'end', 'created', 'modified', 'taxonomy_id'], 'safe'],
			[['active', 'primary'], 'boolean'],
			[['parent_object_id', 'child_object_id'], 'string', 'max' => 36],
			[['special'], 'string', 'max' => 100]
		];
	}

	public function afterSaveRelation($event)
	{
		return true;
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'parent_object_id' => 'Parent Object ID',
			'child_object_id' => 'Child Object ID',
			'start' => 'Start',
			'end' => 'End',
			'active' => 'Active',
			'primary' => 'Primary',
			'special' => 'Special',
			'created' => 'Created',
			'modified' => 'Modified',
		];
	}

	public function getChildObject($checkAccess = true)
	{
		$registryClass = $this->registryClass;
		return $registryClass::getObject($this->child_object_id, $checkAccess);
	}

	public function getParentObject($checkAccess = true)
	{
		$registryClass = $this->registryClass;
		return $registryClass::getObject($this->parent_object_id, $checkAccess);
	}


	// public static function set($parentObject, $childObject = null, $params = [])
	// {
	// 	$baseParams = ['active' => 1];
	// 	if (!is_object($parentObject) && is_array($parentObject) && isset($parentObject['parent_object_id']) && isset($parentObject['child_object_id'])) {
	// 		$newParams = $parentObject;
	// 		$childObject = $newParams['child_object_id'];
	// 		$parentObject = $newParams['parent_object_id'];
	// 		unset($newParams['parent_object_id'], $newParams['child_object_id']);
	// 		$baseParams = array_merge($baseParams, $newParams);
	// 	}
	// 	if (is_object($parentObject)) {
	// 		$parentObject = $parentObject->primaryKey;
	// 	}
	// 	if (is_object($childObject)) {
	// 		$childObject = $childObject->primaryKey;
	// 	}
	// 	$params = array_merge($baseParams, $params);
	// 	$coreFields = ['parent_object_id' => $parentObject, 'child_object_id' => $childObject];
	// 	$object = self::findOne($coreFields, false);
	// 	if (!$object) {
	// 		$className = self::className();
	// 		$object = new $className;
	// 	}
	// 	$object->attributes = array_merge($coreFields, $params);
	// 	if (!$object->save()) {
	// 		return false;
	// 	}
	// 	return $object;
	// }
	
}
