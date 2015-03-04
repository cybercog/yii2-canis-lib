<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\models;

use infinite\db\ActiveRecord;
use infinite\helpers\ArrayHelper;
use Yii;
use yii\web\IdentityInterface;

/**
 * User is the model class for table "user".
 *
 * @property string $authKey Current user auth key. This property is read-only.
 * @property int|string $id Current user ID. This property is read-only.
 * @property \yii\db\ActiveRelation $registry This property is read-only.
 *
 * Class User
 * @property integer $id
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $role
 * @property integer $status
 * @property integer $create_time
 * @property integer $update_time
 * @property string $authKey Current user auth key. This property is read-only.
 * @property int|string $id Current user ID. This property is read-only.
 * @property string $authKey Current user auth key. This property is read-only.
 * @property int|string $id Current user ID. This property is read-only.
 * @property string $authKey Current user auth key. This property is read-only.
 * @property int|string $id Current user ID. This property is read-only.
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class User extends ActiveRecord implements IdentityInterface
{
    /**
     * @var __var__groups_type__ __var__groups_description__
     */
    protected $_groups;
    /**
     * @var string the raw password. Used to collect password input and isn't saved in database
     */
    public $password;

    protected $_identityMeta;
    protected $_identities;
    protected $_identitiesByProvider;
    protected $_activeIdentity;
    protected $_touchedIdentities = [];
    protected $_primaryIdentity;

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
     * Finds an identity by the given ID.
     *
     * @param string|integer $id the ID to be looked for
     *
     * @return IdentityInterface|null the identity object that matches the given ID.
     */
    public static function findIdentity($id)
    {
        $primaryKey = static::primaryKey();

        return static::find()->disableAccessCheck()->andWhere([$primaryKey[0] => $id])->one();
    }

    /**
     * __method_findIdentityByAccessToken_description__.
     *
     * @param __param_token_type__ $token __param_token_description__
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
    }

    public static function findByEmail($email)
    {
        return static::find()->andWhere(['email' => $email, 'status' => static::STATUS_ACTIVE])->disableAccessCheck()->one();
    }

    public function getIdentityMeta()
    {
        if (is_null($this->_identityMeta)) {
            $identityMeta = Yii::$app->session['identityMeta'];
            if (isset($identityMeta[md5($this->primaryKey)])) {
                $this->identityMeta = $identityMeta[md5($this->primaryKey)];
            }
        }

        return $this->_identityMeta;
    }

    public function setIdentityMeta($meta)
    {
        $this->_identityMeta = $meta;
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
     * __method_validateAuthKey_description__.
     *
     * @param string $authKey
     *
     * @return boolean if auth key is valid for current user
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * __method_validatePassword_description__.
     *
     * @param string $password password to validate
     *
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        if ($this->activeIdentity) {
            $identityProvider = $this->activeIdentity->identityProvider;
            $handler = false;
            if ($identityProvider) {
                $handler = $identityProvider->getHandler($this->activeIdentity->token, $this->activeIdentity->meta);
            }
            if ($handler) {
                $result = $handler->validatePassword($this, $password);
                foreach ($handler->errors as $attribute => $errors) {
                    foreach ($errors as $error) {
                        $this->addError($attribute, $error);
                    }
                }
                if (isset(Yii::$app->session)) {
                    $identityMeta = Yii::$app->session['identityMeta'];
                    $identityMeta[md5($this->primaryKey)] = $this->identityMeta = $handler->serverMeta;
                    Yii::$app->session['identityMeta'] = $identityMeta;
                }

                return $result;
            }

            return false;
        }

        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email'], 'filter', 'filter' => 'trim'],
            [['email'], 'filter', 'filter' => 'strtolower'],
            [['email'], 'required'],
            [['primaryIdentity'], 'safe'],

            [['first_name', 'last_name', 'email'], 'string', 'min' => 1, 'max' => 255],

            // ['email', 'required'],
            [['email'], 'email'],
            [['email'], 'unique', 'message' => 'This email address has already been taken.', 'on' => 'signup'],
            [['email'], 'exist', 'message' => 'There is no user with such email.', 'on' => 'requestPasswordResetToken'],

            [['password'], 'required'],
            [['password'], 'string', 'min' => 6],

        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        return [
            'creation' => ['email', 'first_name', 'last_name', 'password'],
            'updateRelations' => [],
            'resetPassword' => ['password'],
            'requestPasswordResetToken' => ['email'],
        ];
    }

    public function getPrimaryIdentity()
    {
        if (!isset($this->_primaryIdentity) && isset($this->primary_identity_id)) {
            $identityClass = Yii::$app->classes['Identity'];
            $this->_primaryIdentity = $identityClass::get($this->primary_identity_id, false);
        }
        if (empty($this->_primaryIdentity)) {
            return;
        }

        return $this->_primaryIdentity;
    }

    public function setPrimaryIdentity($identity)
    {
        $identity = $this->setIdentity($identity);
        $this->_primaryIdentity = $identity;

        return $identity;
    }

    public function setIdentity($identity)
    {
        if (!is_object($identity)) {
            $identityAttributes = $identity;
            $identity = null;
            if (!isset($identityAttributes['identity_provider_id'])) {
                return false;
            }
            if (isset($this->identitiesByProvider[$identityAttributes['identity_provider_id']])) {
                $identity = $this->identitiesByProvider[$identityAttributes['identity_provider_id']];
                Yii::configure($identity, $identityAttributes);
            } else {
                if (!isset($identityAttributes['class'])) {
                    $identityAttributes['class'] = Yii::$app->classes['Identity'];
                }
                $identity = Yii::createObject($identityAttributes);
            }
        } elseif (isset($this->identitiesByProvider[$identity->identity_provider_id])) {
            $newIdentity = $identity;
            $identity = $this->identitiesByProvider[$identity->identity_provider_id];
            $identity->token = $newIdentity->token;
            $identity->meta = $newIdentity->meta;
        }
        $this->_touchedIdentities[] = $identity;

        return $identity;
    }

    public function getIdentities()
    {
        if (!isset($this->_identities)) {
            $this->_identities = [];
            if (!empty($this->primaryKey)) {
                $identityClass = Yii::$app->classes['Identity'];
                $rawIdentities = $identityClass::find()->where(['user_id' => $this->primaryKey])->all();
                $this->_identities = ArrayHelper::index($rawIdentities, 'primaryKey');
            }
        }

        return $this->_identities;
    }

    public function getIdentitiesByProvider()
    {
        if (!isset($this->_identitiesByProvider)) {
            $this->_identitiesByProvider = ArrayHelper::index($this->identities, 'identity_provider_id');
        }

        return $this->_identitiesByProvider;
    }

    public function getActiveIdentity()
    {
        if (!isset($this->_activeIdentity)) {
            $this->_activeIdentity = false;
            if (isset($this->primary_identity_id) && isset($this->identities[$this->primary_identity_id])) {
                $this->_activeIdentity = $this->identities[$this->primary_identity_id];
            }
        }

        return $this->_activeIdentity;
    }

    public function setActiveIdentity($identity)
    {
        $this->_activeIdentity = $identity;
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (($this->isNewRecord || $this->getScenario() === 'resetPassword') && !empty($this->password)) {
                $this->password_hash = Yii::$app->security->generatePasswordHash($this->password);
            }
            if ($this->isNewRecord) {
                $this->auth_key = Yii::$app->security->generateRandomString();
            }
            //\d($this->_touchedIdentities);exit;
            foreach ($this->_touchedIdentities as $identity) {
                if ($identity->user_id !== $this->primaryKey) {
                    $identity->user_id = $this->primaryKey;
                }
                if (!$identity->save()) {
                    return false;
                }
            }

            if (isset($this->primaryIdentity) && $this->primaryIdentity->primaryKey !== $this->primary_identity_id) {
                $this->primary_identity_id = $this->primaryIdentity->primaryKey;
            } elseif (!isset($this->_primaryIdentity) && isset($this->primary_identity_id)) {
                $this->primary_identity_id = null;
            }

            return true;
        }

        return false;
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
     * Get groups.
     *
     * @return __return_getGroups_type__ __return_getGroups_description__
     */
    public function getGroups()
    {
        if (!isset($this->_groups)) {
            $groupClass = Yii::$app->classes['Group'];
            $this->_groups = $this->parents($groupClass);
        }

        return $this->_groups;
    }
}
