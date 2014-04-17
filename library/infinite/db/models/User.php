<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\models;

use Yii;
use infinite\db\ActiveRecord;
use yii\helpers\Security;
use yii\web\IdentityInterface;

/**
 * User is the model class for table "user".
 *
 *
 * @property string $authKey Current user auth key. This property is read-only.
 * @property int|string $id Current user ID. This property is read-only.
 * @property \yii\db\ActiveRelation $registry This property is read-only.
 *
 * Class User
 * @package common\models
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $role
 * @property integer $status
 * @property integer $create_time
 * @property integer $update_time
 * 
 * @author Jacob Morrison <email@ofjacob.com>
 */
class User extends ActiveRecord implements IdentityInterface
{
    protected $_groups;
    /**
     * @var string the raw password. Used to collect password input and isn't saved in database
     */
    public $password;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_RESET = 2;

    const ROLE_USER = 10;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user';
    }

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
     * Finds an identity by the given ID.
     *
     * @param  string|integer         $id the ID to be looked for
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        $primaryKey = static::primaryKey();

        return static::find()->disableAccessCheck()->andWhere([$primaryKey[0] => $id])->one();
    }

    public static function findIdentityByAccessToken($token)
    {
    }

    /**
     * Finds user by username
     *
     * @param  string    $username
     * @return null|User
     */
    public static function findByUsername($username)
    {
        return static::find()->andWhere(['username' => $username, 'status' => static::STATUS_ACTIVE])->disableAccessCheck()->one();
    }

    /**
     * @return int|string current user ID
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string current user auth key
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @param  string  $authKey
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * @param  string $password password to validate
     * @return bool   if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Security::validatePassword($password, $this->password_hash);
    }

    public function rules()
    {
        return [
            [['username'], 'filter', 'filter' => 'trim'],
            [['username'], 'required'],
            [['username'], 'string', 'min' => 2, 'max' => 255],
            [['first_name', 'last_name', 'email'], 'string', 'min' => 1, 'max' => 255],

            [['email'], 'filter', 'filter' => 'trim'],

            // ['email', 'required'],
            [['email'], 'email'],
            [['email'], 'unique', 'message' => 'This email address has already been taken.', 'on' => 'signup'],
            [['email'], 'exist', 'message' => 'There is no user with such email.', 'on' => 'requestPasswordResetToken'],

            [['password'], 'required'],
            [['password'], 'string', 'min' => 6],
        ];
    }

    public function scenarios()
    {
        return [
            'creation' => ['username', 'email', 'first_name', 'last_name', 'password'],
            'resetPassword' => ['password'],
            'requestPasswordResetToken' => ['email'],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (($this->isNewRecord || $this->getScenario() === 'resetPassword') && !empty($this->password)) {
                $this->password_hash = Security::generatePasswordHash($this->password);
            }
            if ($this->isNewRecord) {
                $this->auth_key = Security::generateRandomKey();
            }

            return true;
        }

        return false;
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getRegistry()
    {
        return $this->hasOne('Registry', ['id' => 'id']);
    }

    public function getGroups()
    {
        if (!isset($this->_groups)) {
            $groupClass = Yii::$app->classes['Group'];
            $this->_groups = $this->parents($groupClass);
        }

        return $this->_groups;
    }

    public function guessIndividual()
    {
        $individualTypeItem = Yii::$app->collectors['types']->getOne('Individual');
        $individualClass = $individualTypeItem->object->primaryModel;
        $emailTypeItem = Yii::$app->collectors['types']->getOne('EmailAddress');
        $emailTypeClass = $emailTypeItem->object->primaryModel;
        $emailMatch = $emailTypeClass::find()->where(['email_address' => $this->email])->disableAccessCheck()->all();
        $individuals = [];
        foreach ($emailMatch as $email) {
            if (($individual = $email->parent($individualClass, [], ['disableAccessCheck' => true])) && $individual) {
                $individuals[$individual->primaryKey] = $individual;
            }
        }
        if (empty($individuals)) {
            if (($individualMatch = $individualClass::find()->where(['first_name' => $this->first_name, 'last_name' => $this->last_name])->one()) && $individualMatch) {
                return $individualMatch;
            }
        } else {
            if (count($individuals) === 1) {
                return array_pop($individuals);
            }

            return $individuals;
        }

        return false;
    }
}
