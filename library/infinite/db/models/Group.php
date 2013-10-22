<?php

namespace infinite\db\models;

/**
 * This is the model class for table "group".
 *
 * @property string $id
 * @property string $name
 * @property string $system
 * @property integer $level
 * @property string $created
 * @property string $modified
 *
 * @property Registry $id
 */
class Group extends \infinite\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'group';
	}

    public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [
                'Registry' => '\infinite\db\behaviors\Registry',
            ]
        );
    }

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['name', 'required'],
			['level', 'integer'],
			['created, modified', 'safe'],
			['id', 'string', 'max' => 36],
			['name', 'string', 'max' => 100],
			['system', 'string', 'max' => 20]
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'name' => 'Name',
			'system' => 'System',
			'level' => 'Level',
			'created' => 'Created',
			'modified' => 'Modified',
		];
	}

	/**
	 * @return \yii\db\ActiveRelation
	 */
	public function getRegistry()
	{
		return $this->hasOne('Registry', ['id' => 'id']);
	}
}
