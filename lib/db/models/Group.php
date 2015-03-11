<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\models;

use yii\web\IdentityInterface;

/**
 * Group is the model class for table "group".
 *
 * @property string $id
 * @property string $name
 * @property string $system
 * @property integer $level
 * @property string $created
 * @property string $modified
 * @property Registry $id
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Group extends \teal\db\ActiveRecord implements IdentityInterface
{
    /**
     * @inheritdoc
     */
    static protected $_cache = ['id' => [], 'system' => []];

    /**
     * @var [[@doctodo var_type:_doCache]] [[@doctodo var_description:_doCache]]
     */
    protected static $_doCache = true;

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
                    'class' => 'teal\db\behaviors\Registry',
                ],
                'Relatable' => [
                    'class' => 'teal\db\behaviors\Relatable',
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
            [['name'], 'required'],
            [['level'], 'integer'],
            [['created', 'modified'], 'safe'],
            [['id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 100],
            [['system'], 'string', 'max' => 20],
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
     * Get registry.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getRegistry()
    {
        return $this->hasOne('Registry', ['id' => 'id']);
    }

    /**
     * Get system group.
     *
     * @param [[@doctodo param_type:system]] $system [[@doctodo param_description:system]]
     *
     * @return [[@doctodo return_type:getSystemGroup]] [[@doctodo return_description:getSystemGroup]]
     */
    public static function getSystemGroup($system)
    {
        // @todo add cache
        $system = Group::model()->field('system', $system)->find();

        return $system;
    }

    /**
     * [[@doctodo method_description:enableCache]].
     */
    public static function enableCache()
    {
        self::$_doCache = true;
    }

    /**
     * [[@doctodo method_description:disableCache]].
     */
    public static function disableCache()
    {
        self::$_doCache = false;
    }

    /**
     * Get by.
     *
     * @param unknown $id
     * @param boolean $checkAccess [[@doctodo param_description:checkAccess]] [optional]
     *
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
        if (empty($group)) {
            return false;
        }
        // @todo security of both items?

        if ($group and self::$_doCache) {
            self::$_cache['id'][$id] = $group;
        }

        return $group;
    }

    /**
     * Get by system name.
     *
     * @param [[@doctodo param_type:id]] $id          [[@doctodo param_description:id]]
     * @param boolean                    $checkAccess [[@doctodo param_description:checkAccess]] [optional]
     *
     * @return [[@doctodo return_type:getBySystemName]] [[@doctodo return_description:getBySystemName]]
     */
    public static function getBySystemName($id, $checkAccess = true)
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
        if (empty($group)) {
            return false;
        }
        // @todo security of both items?

        if ($group && self::$_doCache) {
            self::$_cache['system'][$id] = $group;
        }

        return $group;
    }

    /**
     * [[@doctodo method_description:findIdentity]].
     *
     * @param [[@doctodo param_type:id]] $id [[@doctodo param_description:id]]
     *
     * @return [[@doctodo return_type:findIdentity]] [[@doctodo return_description:findIdentity]]
     */
    public static function findIdentity($id)
    {
        $primaryKey = static::primaryKey();

        return static::find()->disableAccessCheck()->andWhere([$primaryKey[0] => $id])->one();
    }

    /**
     * [[@doctodo method_description:findIdentityByAccessToken]].
     *
     * @param [[@doctodo param_type:token]] $token [[@doctodo param_description:token]]
     * @param [[@doctodo param_type:type]]  $type  [[@doctodo param_description:type]] [optional]
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
    }

    /**
     * Get id.
     *
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get auth key.
     *
     * @return string current user auth key
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * [[@doctodo method_description:validateAuthKey]].
     *
     * @param string $authKey
     *
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
}
