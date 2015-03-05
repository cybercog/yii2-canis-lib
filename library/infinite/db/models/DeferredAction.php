<?php

namespace infinite\db\models;

use Yii;

/**
 * DeferredAction is the model class for table "deferred_action".
 *
 * @property string $id
 * @property string $user_id
 * @property string $status
 * @property resource $action
 * @property string $created
 * @property string $modified
 * @property User $user
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class DeferredAction extends \infinite\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'deferred_action';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['status', 'action'], 'string'],
            [['action', 'type'], 'required'],
            [['created', 'modified', 'expired'], 'safe'],
            [['user_id'], 'string', 'max' => 36],
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
            'status' => 'Status',
            'type' => 'Type',
            'action' => 'Action',
            'created' => 'Created',
            'modified' => 'Modified',
            'expired' => 'Modified',
        ];
    }

    /**
     * Get user.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * Get my recent pending query.
     *
     * @return [[@doctodo return_type:getMyRecentPendingQuery]] [[@doctodo return_description:getMyRecentPendingQuery]]
     */
    public function getMyRecentPendingQuery($type = null, $user = null)
    {
        if (is_null($user) && !empty(Yii::$app->user->id)) {
            $user = Yii::$app->user->id;
        }
        if (empty($user)) {
            return false;
        }
        if (is_object($user)) {
            $user = $user->primaryKey;
        }

        $query = static::find();
        $query->andWhere(['user_id' => $user]);
        $query->andWhere(['or',
            ['status' => 'queued'],
            ['or', 'expired IS NULL', 'expired > NOW()'],
        ]);
        if (!is_null($type)) {
            $query->andWhere(['type' => $type]);
        }

        return $query;
    }
}
