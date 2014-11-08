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
    /**
     * @inheritdoc
     */
    static protected $_cache = ['id' => [], 'system' => []];
    /**
     * @var __var__doCache_type__ __var__doCache_description__
     */
    static protected $_doCache = true;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'group';
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
     * Get registry
     * @return \yii\db\ActiveRelation
     */
    public function getRegistry()
    {
        return $this->hasOne('Registry', ['id' => 'id']);
    }

    /**
     * Get system group
     * @param unknown $system
     * @return unknown
     */
    static public function getSystemGroup($system)
    {
        // @todo add cache
        $system = Group::model()->field('system', $system)->find();

        return $system;
    }

    /**
     * __method_enableCache_description__
     */
    static function enableCache()
    {
        self::$_doCache = true;
    }

    /**
     * __method_disableCache_description__
     */
    static function disableCache()
    {
        self::$_doCache = false;
    }

    /**
     * Get by
     * @param unknown $id
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
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
     * Get by system name
     * @param unknown $id
     * @param boolean $checkAccess __param_checkAccess_description__ [optional]
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
        if (!$group || $group->system !== $id) {
        }
        if (empty($group)) { return false; }
        // @todo security of both items?

        if ($group && self::$_doCache) {
            self::$_cache['system'][$id] = $group;
        }

        return $group;
    }
}
