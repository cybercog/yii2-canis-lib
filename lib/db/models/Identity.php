<?php

namespace teal\db\models;

use Yii;

/**
 * Identity is the model class for table "identity".
 *
 * @property string $id
 * @property string $user_id
 * @property string $identity_provider_id
 * @property string $token
 * @property string $meta
 * @property string $created
 * @property string $modified
 * @property IdentityProvider $identityProvider
 * @property User $id0
 * @property User[] $users
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Identity extends \teal\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_VALIDATE, [$this, 'metaToSerial']);
        $this->on(self::EVENT_AFTER_SAVE_FAIL, [$this, 'metaToSerial']);
        $this->on(self::EVENT_AFTER_FIND, [$this, 'metaToArray']);
    }

    /**
     * [[@doctodo method_description:metaToSerial]].
     */
    public function metaToSerial()
    {
        $this->meta = serialize($this->meta);
    }

    /**
     * [[@doctodo method_description:metaToArray]].
     */
    public function metaToArray()
    {
        $this->meta = unserialize($this->meta);
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'identity';
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
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'identity_provider_id'], 'required'],
            [['token', 'meta'], 'string'],
            [['created', 'modified'], 'safe'],
            [['id', 'user_id', 'identity_provider_id'], 'string', 'max' => 36],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'identity_provider_id' => 'Identity Provider ID',
            'token' => 'Token',
            'meta' => 'Meta',
            'created' => 'Created',
            'modified' => 'Modified',
        ];
    }

    /**
     * Get identity provider.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdentityProvider()
    {
        $idp = Yii::$app->collectors['identityProviders']->getById($this->identity_provider_id);
        if (!empty($idp) && $idp->object) {
            return $idp;
        }

        return false;
    }

    /**
     * Get id0.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getId0()
    {
        return $this->hasOne(Yii::$app->classes['User'], ['id' => 'id']);
    }

    /**
     * Get users.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(Yii::$app->classes['User'], ['primary_identity_id' => 'id']);
    }
}
