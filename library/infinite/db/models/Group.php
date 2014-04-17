<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\models;

/**
 * Group is the model class for table "group".
 *
 *
 * @property string $id
 * @property string $name
 * @property string $system
 * @property integer $level
 * @property string $created
 * @property string $modified
 *
 * @property Registry $id
 * 
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Group extends \infinite\db\ActiveRecord
{
    static protected $_cache = ['id' => [], 'system' => []];
    static protected $_doCache = true;
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
                'Registry' => [
                    'class' => 'infinite\db\behaviors\Registry',
                ],
                'Relatable' => [
                    'class' => 'infinite\db\behaviors\Relatable',
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
            [['name'], 'required'],
            [['level'], 'integer'],
            [['created', 'modified'], 'safe'],
            [['id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 100],
            [['system'], 'string', 'max' => 20]
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

    /**
     *
     *
     * @param  unknown $system
     * @return unknown
     */
    static public function getSystemGroup($system)
    {
        // @todo add cache
        $system = Group::model()->field('system', $system)->find();

        return $system;
    }

    /**
     *
     */
    static function enableCache()
    {
        self::$_doCache = true;
    }

    /**
     *
     */
    static function disableCache()
    {
        self::$_doCache = false;
    }

    /**
     *
     *
     * @param  unknown $id
     * @return unknown
     */
    public static function getById($id, $checkAccess = true)
    {
        if (isset(self::$_cache['id'][$id])) {
            return self::$_cache['id'][$id];
        }
        $group = self::model();
        if (!$checkAccess) {
            $group->disableAccessCheck();
        }
        $group = $group->findByPk($id);
        if (empty($group)) { return false; }
        // @todo security of both items?

        if ($group and self::$_doCache) {
            self::$_cache['id'][$id] = $group;
        }

        return $group;
    }

    /**
     *
     *
     * @param  unknown $id
     * @return unknown
     */
    static function getBySystemName($id, $checkAccess = true)
    {
        if (isset(self::$_cache['system'][$id])) {
            return self::$_cache['system'][$id];
        }
        $group = $groupQuery = self::find()->where(['system' => $id]);
        if (!$checkAccess) {
            $group->disableAccessCheck();
        }
        $group = $group->one();
        if (!$group OR $group->system !== $id) {
        }
        if (empty($group)) { return false; }
        // @todo security of both items?

        if ($group and self::$_doCache) {
            self::$_cache['system'][$id] = $group;
        }

        return $group;
    }
}
