<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\models;

/**
 * Aca is the model class for table "aca".
 *
 *
 * @property string $id
 * @property string $name
 * @property string $created
 * @property string $modified
 *
 * @property \yii\db\ActiveRelation $registry This property is read-only.
 *
 * @property Registry $id
 */
 * @author Jacob Morrison <email@ofjacob.com>
class Aca extends \infinite\db\ActiveRecord
{
    public static $registryCache = false;
    public static $relationCache = false;
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
