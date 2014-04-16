<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\models;

use Yii;

/**
 * This is the model class for table "audit".
 *
 * @property string $id
 * @property string $agent_id
 * @property string $direct_object_id
 * @property string $indirect_object_id
 * @property string $event_id
 * @property string $event
 * @property bool $hooks_handled
 * @property string $created
 *
 * @property Registry $agent
 * @property Registry $directObject
 * @property Registry $indirectObject
 */
class Audit extends \infinite\db\ActiveRecord
{
    public static $registryCache = false;
    public static $relationCache = false;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'audit';
    }

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
    public function behaviors()
    {
        return array_merge(parent::behaviors(), []);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['agent_id', 'direct_object_id', 'event_id'], 'required'],
            [['event'], 'string'],
            [['hooks_handled'], 'boolean'],
            [['agent_id', 'direct_object_id', 'indirect_object_id'], 'string', 'max' => 36],
            [['event_id'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'agent_id' => 'Agent ID',
            'direct_object_id' => 'Direct Object ID',
            'indirect_object_id' => 'Indirect Object ID',
            'event_id' => 'Event ID',
            'event' => 'Event',
            'hooks_handled' => 'Hooks Handled',
            'created' => 'Created',
        ];
    }

    public function getEventObject()
    {
        try {
            $event = unserialize($this->event);
        } catch (\Exception $e) {
            $event = false;
        }

        return $event;
    }

    public function handleHooks($save = true)
    {
        if (!empty($this->hooks_handled)) {
            return true;
        }
        $eventObject = $this->eventObject;
        if ($eventObject && $$eventObject->handleHooks()) {
            $this->hooks_handled = 1;
            if ($save) {
                return $this->save();
            }

            return true;
        }

        return false;
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getAgent()
    {
        return $this->hasOne(Yii::$app->classes['Registry'], ['id' => 'agent_id']);
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getDirectObject()
    {
        return $this->hasOne(Yii::$app->classes['Registry'], ['id' => 'direct_object_id']);
    }

    /**
     * @return \yii\db\ActiveRelation
     */
    public function getIndirectObject()
    {
        return $this->hasOne(Yii::$app->classes['Registry'], ['id' => 'indirect_object_id']);
    }
}
