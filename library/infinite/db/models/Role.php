<?php

namespace infinite\db\models;

/**
 * This is the model class for table "role".
 *
 * @property string $id
 * @property string $name
 * @property string $system_id
 * @property double $system_version
 * @property string $created
 * @property string $modified
 *
 * @property Registry $id
 */
class Role extends \infinite\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'role';
	}

	/**
	 * @inheritdoc
	 */
    public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [
                'Registry' => '\infinite\db\behaviors\Registry',
                'Relatable' => '\infinite\db\behaviors\Relatable',
            ]
        );
    }
    
	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['system_version', 'number'],
			['created, modified', 'safe'],
			['id', 'string', 'max' => 36],
			['name, system_id', 'string', 'max' => 100]
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
			'system_id' => 'System ID',
			'system_version' => 'System Version',
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
