<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\models;

use Yii;

/**
 * Audit is the model class for table "audit".
 *
 * @property string $id
 * @property string $agent_id
 * @property string $direct_object_id
 * @property string $indirect_object_id
 * @property string $event_id
 * @property string $event
 * @property bool $hooks_handled
 * @property string $created
 * @property Registry $agent
 * @property Registry $directObject
 * @property Registry $indirectObject
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Audit extends \teal\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static $registryCache = false;
    /**
     * @inheritdoc
     */
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
            [['agent_id', 'event_id'], 'required'],
            [['event'], 'string'],
            [['hooks_handled'], 'boolean'],
            [['agent_id', 'direct_object_id', 'indirect_object_id'], 'string', 'max' => 36],
            [['event_id'], 'string', 'max' => 50],
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

    /**
     * Get event object.
     *
     * @return [[@doctodo return_type:getEventObject]] [[@doctodo return_description:getEventObject]]
     */
    public function getEventObject()
    {
        try {
            $event = unserialize($this->event);
        } catch (\Exception $e) {
            $event = false;
        }
        $event->model = $this;

        return $event;
    }

    /**
     * [[@doctodo method_description:handleHooks]].
     *
     * @param boolean $save [[@doctodo param_description:save]] [optional]
     *
     * @return [[@doctodo return_type:handleHooks]] [[@doctodo return_description:handleHooks]]
     */
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
     * Get agent.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getAgent()
    {
        return $this->hasOne(Yii::$app->classes['Registry'], ['id' => 'agent_id']);
    }

    /**
     * Get direct object.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getDirectObject()
    {
        return $this->hasOne(Yii::$app->classes['Registry'], ['id' => 'direct_object_id']);
    }

    /**
     * Get indirect object.
     *
     * @return \yii\db\ActiveRelation
     */
    public function getIndirectObject()
    {
        return $this->hasOne(Yii::$app->classes['Registry'], ['id' => 'indirect_object_id']);
    }
}
