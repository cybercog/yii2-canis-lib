<?php

namespace infinite\db\models;

/**
 * This is the model class for table "Aca".
 *
 * @property string $id
 * @property string $name
 * @property string $created
 * @property string $modified
 *
 * @property Registry $id
 */
class Aca extends \infinite\db\ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return 'aca';
	}

	/**
	 * @inheritdoc
	 */
    public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [
                'Registry' => 'infinite\db\behaviors\Registry',
                'Relatable' => 'infinite\db\behaviors\Relatable',
            ]
        );
    }
	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name'], 'required'],
			[['created', 'modified'], 'safe'],
			[['id'], 'string', 'max' => 36],
			[['name'], 'string', 'max' => 100]
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
