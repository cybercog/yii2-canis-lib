<?php

namespace infinite\db\models;

use Yii;

/**
 * This is the model class for table "identity".
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
 */
class Identity extends \infinite\db\ActiveRecord
{
    public function init()
    {
        parent::init();
        $this->on(self::EVENT_BEFORE_VALIDATE, [$this, 'metaToSerial']);
        $this->on(self::EVENT_AFTER_SAVE_FAIL, [$this, 'metaToSerial']);
        $this->on(self::EVENT_AFTER_FIND, [$this, 'metaToArray']);
    }

    public function metaToSerial()
    {
        $this->meta = serialize($this->meta);
    }

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
                    'class' => 'infinite\db\behaviors\Registry',
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
     * @return \yii\db\ActiveQuery
     */
    public function getId0()
    {
        return $this->hasOne(Yii::$app->classes['User'], ['id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(Yii::$app->classes['User'], ['primary_identity_id' => 'id']);
    }
}
