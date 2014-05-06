<?php

namespace infinite\db\models;

use Yii;
use infinite\base\collector\CollectedObjectTrait;

/**
 * This is the model class for table "identity_provider".
 *
 * @property string $id
 * @property string $name
 * @property string $system_id
 * @property string $handler
 * @property string $created
 * @property string $modified
 *
 * @property Identity[] $identities
 * @property Registry $id0
 */
class IdentityProvider extends \infinite\db\ActiveRecord implements \infinite\base\collector\CollectedObjectInterface
{
    use CollectedOBjectTrait;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'identity_provider';
    }

    /**
    * @inheritdoc
     */
    public function behaviors()
    {
        return array_merge(parent::behaviors(),
            [
                'Registry' => [
                    'class' => 'infinite\db\behaviors\Registry',
                ]
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['created', 'modified'], 'safe'],
            [['id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 200],
            [['system_id', 'handler'], 'string', 'max' => 100],
            [['system_id'], 'unique']
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
            'handler' => 'Handler',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIdentities()
    {
        return $this->hasMany(Identity::className(), ['identity_provider_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getId0()
    {
        return $this->hasOne(Registry::className(), ['id' => 'id']);
    }
}
