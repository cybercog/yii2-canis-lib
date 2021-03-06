<?php

namespace canis\db\models;

use canis\base\collector\CollectedObjectTrait;
use Yii;

/**
 * IdentityProvider is the model class for table "identity_provider".
 *
 * @property string $id
 * @property string $name
 * @property string $system_id
 * @property string $handler
 * @property string $created
 * @property string $modified
 * @property Identity[] $identities
 * @property Registry $id0
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class IdentityProvider extends \canis\db\ActiveRecord implements \canis\base\collector\CollectedObjectInterface
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
                    'class' => 'canis\db\behaviors\Registry',
                ],
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
            [['system_id'], 'unique'],
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
     * Get identities.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdentities()
    {
        return $this->hasMany(Identity::className(), ['identity_provider_id' => 'id']);
    }

    /**
     * Get registry.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegistry()
    {
        return $this->hasOne(Registry::className(), ['id' => 'id']);
    }
}
