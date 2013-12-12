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
	static $_callCache = [];

	public function events()
    {
        return array_merge(parent::events(), [
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
        ]);
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

	public function getChildObject()
	{
		return Registry::getObject($this->child_object_id);
	}

	public function getParentObject()
	{
		return Registry::getObject($this->parent_object_id);
	}

	
}
