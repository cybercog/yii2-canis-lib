<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\models;

use canis\base\collector\CollectedObjectTrait;

/**
 * Role is the model class for table "role".
 *
 * @property string $id
 * @property string $name
 * @property string $system_id
 * @property double $system_version
 * @property string $created
 * @property string $modified
 * @property Registry $id
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Role extends \canis\db\ActiveRecord implements \canis\base\collector\CollectedObjectInterface
{
    use CollectedOBjectTrait;
    /**
     * @var [[@doctodo var_type:roleableEnabled]] [[@doctodo var_description:roleableEnabled]]
     */
    public $roleableEnabled = false;

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
                'Registry' => 'canis\db\behaviors\Registry',
                'Relatable' => 'canis\db\behaviors\Relatable',
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
            [['name', 'system_id'], 'string', 'max' => 100],
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
     * Get registry.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getRegistry()
    {
        return $this->hasOne('Registry', ['id' => 'id']);
    }
}
