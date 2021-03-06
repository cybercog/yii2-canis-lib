<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\db\models;

/**
 * Aca is the model class for table "aca".
 *
 * @property string $id
 * @property string $name
 * @property string $created
 * @property string $modified
 * @property \yii\db\ActiveRelation $registry This property is read-only.
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Aca extends \canis\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static $registryCache = false;
    /**
     * @inheritdoc
     */
    public static $relationCache = false;
    /**
     * @inheritdoc
     */
    public static $groupCache = true;

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
        return 'aca';
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
            [['name'], 'required'],
            [['created', 'modified'], 'safe'],
            [['id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 100],
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
     * Get registry.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getRegistry()
    {
        return $this->hasOne('Registry', ['id' => 'id']);
    }
}
