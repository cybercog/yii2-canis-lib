<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\db\behaviors\auditable;

use teal\caching\Cacher;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Auditable [[@doctodo class_description:teal\db\behaviors\auditable\Auditable]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Auditable extends \teal\db\behaviors\ActiveRecord
{
    /**
     * @var int What is defined as recent? Used when ignoring relation saves
     */
    const RECENT_IN_SECONDS = 300;
    /**
     * @var string Audit event base class
     */
    public $baseEventClass = 'teal\db\behaviors\auditable\BaseEvent';
    /**
     * @var string Audit insert event class
     */
    public $createEventClass = 'teal\db\behaviors\auditable\CreateEvent';
    /**
     * @var string Audit uodate event class
     */
    public $updateEventClass = 'teal\db\behaviors\auditable\UpdateEvent';
    /**
     * @var string Audit delete event class
     */
    public $deleteEventClass = 'teal\db\behaviors\auditable\DeleteEvent';
    /**
     * @var bool Enable all log events
     */
    public $enableLogging = true;
    /**
     * @var bool Enable save log events
     */
    public $enableSaveLog = true;
    /**
     * @var bool Enable delete log events
     */
    public $enableDeleteLog = true;
    /**
     * @var bool Enable the colllection of audit logs
     */
    public $collectBehaviorLogs = true;
    /**
     * @var array Attributes to ignore
     */
    protected $_ignoreAttributes = ['modified', 'created', 'created_by_id', 'modified_by_id'];
    /**
     * @var null|array List of dirty attributes
     */
    protected $_dirtyAttributes;
    /**
     * @var object Object that is being directly edited
     */
    protected $_directObject;
    /**
     * @var object Object that is being edited via relation
     */
    protected $_indirectObject;
    /**
     * @var object Entity that is making the changes
     */
    protected $_auditAgent;
    /**
     * @var [[@doctodo var_type:_auditTimestamp]] [[@doctodo var_description:_auditTimestamp]]
     */
    protected $_auditTimestamp;
    /**
     * @var array Tracking of event logs
     */
    protected $_auditEvents = [];
    /**
     * @var object Mute all log events further down the chain
     */
    protected static $_auditMute;

    /**
     * @event Collect audit on object save (insert and update)
     */
    const EVENT_COLLECT_AUDIT_SAVE = 'collectAuditSave';
    /**
     * @event Collect audit on object insert
     */
    const EVENT_COLLECT_AUDIT_INSERT = 'collectAuditInsert';
    /**
     * @event Collect audit on object update
     */
    const EVENT_COLLECT_AUDIT_UPDATE = 'collectAuditInsert';
    /**
     * @event Collect audit on object delete
     */
    const EVENT_COLLECT_AUDIT_DELETE = 'collectAuditDelete';

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \teal\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            \teal\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            \teal\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            \teal\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',

            \teal\db\ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            \teal\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * @inheritdoc
     */
    public function safeAttributes()
    {
        return ['directObject', 'indirectObject', 'auditAgent', 'auditTimestamp'];
    }

    /**
     * Get audit dirty attributes.
     *
     * @return [[@doctodo return_type:getAuditDirtyAttributes]] [[@doctodo return_description:getAuditDirtyAttributes]]
     */
    public function getAuditDirtyAttributes()
    {
        return $this->_dirtyAttributes;
    }

    /**
     * [[@doctodo method_description:suppressAudit]].
     *
     * @return [[@doctodo return_type:suppressAudit]] [[@doctodo return_description:suppressAudit]]
     */
    public function suppressAudit()
    {
        $this->_enableLogging = false;

        return $this->owner;
    }

    /**
     * [[@doctodo method_description:enableLogging]].
     *
     * @return [[@doctodo return_type:enableLogging]] [[@doctodo return_description:enableLogging]]
     */
    public function enableLogging()
    {
        $this->_enableLogging = true;

        return $this->owner;
    }

    /**
     * Set enable logging.
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     *
     * @return [[@doctodo return_type:setEnableLogging]] [[@doctodo return_description:setEnableLogging]]
     */
    public function setEnableLogging($value)
    {
        $this->_enableLogging = $value;

        return $this->owner;
    }

    /**
     * Get enable logging.
     *
     * @return [[@doctodo return_type:getEnableLogging]] [[@doctodo return_description:getEnableLogging]]
     */
    public function getEnableLogging()
    {
        return $this->_enableLogging;
    }

    /**
     * [[@doctodo method_description:behaviors]].
     *
     * @return [[@doctodo return_type:behaviors]] [[@doctodo return_description:behaviors]]
     */
    public function behaviors()
    {
        return [];
    }

    /**
     * [[@doctodo method_description:prepareEventObject]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:prepareEventObject]] [[@doctodo return_description:prepareEventObject]]
     */
    public function prepareEventObject($event)
    {
        return $event;
    }

    /**
     * [[@doctodo method_description:registerAuditEvent]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @throws InvalidConfigException [[@doctodo exception_description:InvalidConfigException]]
     * @return [[@doctodo return_type:registerAuditEvent]] [[@doctodo return_description:registerAuditEvent]]
     *
     */
    public function registerAuditEvent($event)
    {
        if (is_array($event)) {
            if (!isset($event['class'])) {
                $event['class'] = $this->baseEventClass;
            }
            if (!isset($event['agent'])) {
                $event['agent'] = $this->auditAgent;
            }
            $event = Yii::createObject($event);
        }
        $event->attachBehaviors($this->behaviors());
        $this->prepareEventObject($event);
        if (!($event instanceof Event)) {
            throw new InvalidConfigException("Invalid audit event thrown");
        }
        if ($event->isValid()) {
            if (!isset($this->_auditEvents)) {
                $this->_auditEvents = [];
            }
            $this->_auditEvents[$event->id] = $event;
            if ($event->saveOnRegister) {
                if (!$event->save()) {
                    return false;
                }
            }

            return $event;
        }

        return false;
    }

    /**
     * [[@doctodo method_description:handleAuditSave]].
     *
     * @return [[@doctodo return_type:handleAuditSave]] [[@doctodo return_description:handleAuditSave]]
     */
    public function handleAuditSave()
    {
        if (!$this->owner->isAuditEnabled() || empty($this->_auditEvents)) {
            return true;
        }
        $exclusive = false;
        $events = $this->_auditEvents;

        // merge necessary items
        foreach ($events as $eventKey => $event) {
            if ($event->mergeWith) {
                $mergeWith = $event->mergeWith;
                if (!is_array($mergeWith)) {
                    $mergeWith = [$event->mergeWith];
                }
                foreach ($mergeWith as $checkMerge) {
                    if (isset($events[$checkMerge])) {
                        $events[$checkMerge]->merge($event);
                        unset($events[$eventKey]);
                        break;
                    }
                }
            }
        }

        // if we have an exclusive event, take the first one
        foreach ($events as $event) {
            if ($event->exclusive) {
                $exclusive = $event;
                break;
            }
        }
        if ($exclusive) {
            $events = [$exclusive];
        }

        // now save
        foreach ($events as $event) {
            $auditModel = $event->save();
            if ($auditModel) {
                $this->registerRecentEventSave($auditModel, $event);
            }
        }
        $this->_auditEvents = null;

        return true;
    }

    /**
     * [[@doctodo method_description:registerRecentEventSave]].
     *
     * @param [[@doctodo param_type:auditModel]]    $auditModel [[@doctodo param_description:auditModel]]
     * @param teal\db\behaviors\auditable\Event $event      [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:registerRecentEventSave]] [[@doctodo return_description:registerRecentEventSave]]
     */
    public function registerRecentEventSave($auditModel, Event $event)
    {
        if (empty($auditModel) || !is_object($auditModel)) {
            return;
        }
        $cacheKeys = [
            ['eventSave', $event->id, $event->directObjectId],
            ['eventSave', 'any', $event->directObjectId],
        ];
        foreach ($cacheKeys as $key) {
            Cacher::set($key, $auditModel->primaryKey, static::RECENT_IN_SECONDS);
        }
    }

    /**
     * Get recent event.
     *
     * @param string $eventId [[@doctodo param_description:eventId]] [optional]
     *
     * @return [[@doctodo return_type:getRecentEvent]] [[@doctodo return_description:getRecentEvent]]
     */
    public function getRecentEvent($eventId = 'any')
    {
        if (empty($this->owner->primaryKey)) {
            return false;
        }

        return Cacher::get(['eventSave', $eventId, $this->owner->primaryKey]);
    }

    /**
     * Set audit agent.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     */
    public function setAuditAgent($object)
    {
        $this->_auditAgent = $object;
    }

    /**
     * Set audit agent.
     *
     * @param [[@doctodo param_type:audit]] $audit [[@doctodo param_description:audit]]
     */
    public function setAuditTimestamp($audit)
    {
        $this->_auditTimestamp = $audit;
    }

    /**
     * Get audit timestamp.
     *
     * @return [[@doctodo return_type:getAuditTimestamp]] [[@doctodo return_description:getAuditTimestamp]]
     */
    public function getAuditTimestamp()
    {
        return $this->_auditTimestamp;
    }

    /**
     * Get audit agent.
     *
     * @return [[@doctodo return_type:getAuditAgent]] [[@doctodo return_description:getAuditAgent]]
     */
    public function getAuditAgent()
    {
        if (is_null($this->_auditAgent)) {
            if (isset(Yii::$app->user)) {
                $this->_auditAgent = Yii::$app->user->identity;
            } else {
                $userClass = Yii::$app->classes['User'];
                $this->_auditAgent = $userClass::systemUser();
            }
        }

        return $this->_auditAgent;
    }

    /**
     * Set direct object.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     */
    public function setDirectObject($object)
    {
        $this->_directObject = $object;
    }

    /**
     * Get direct object.
     *
     * @return [[@doctodo return_type:getDirectObject]] [[@doctodo return_description:getDirectObject]]
     */
    public function getDirectObject()
    {
        if (is_null($this->_directObject)) {
            $this->_directObject = $this->owner;
        }

        return $this->_directObject;
    }

    /**
     * Set indirect object.
     *
     * @param [[@doctodo param_type:object]] $object [[@doctodo param_description:object]]
     */
    public function setIndirectObject($object)
    {
        $this->_indirectObject = $object;
    }

    /**
     * Get indirect object.
     *
     * @return [[@doctodo return_type:getIndirectObject]] [[@doctodo return_description:getIndirectObject]]
     */
    public function getIndirectObject()
    {
        return $this->_indirectObject;
    }

    /**
     * [[@doctodo method_description:beforeSave]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeSave]] [[@doctodo return_description:beforeSave]]
     */
    public function beforeSave($event)
    {
        if (!$this->owner->isAuditEnabled()) {
            return true;
        }
        $this->muteAudit();
        // capture dirty attributes
        $this->_dirtyAttributes = $this->owner->dirtyAttributes;
        foreach ($this->_dirtyAttributes as $name => $value) {
            if (in_array($name, $this->ignoreAttributes)) {
                unset($this->_dirtyAttributes[$name]);
            }
        }
    }

    /**
     * [[@doctodo method_description:afterUpdate]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:afterUpdate]] [[@doctodo return_description:afterUpdate]]
     */
    public function afterUpdate($event)
    {
        if (!$this->owner->isAuditEnabled()) {
            return true;
        }
        if ($this->owner->enableSaveLog && !empty($this->auditDirtyAttributes)) {
            $this->registerAuditEvent([
                'class' => $this->updateEventClass,
                'directObject' => $this->directObject,
                'indirectObject' => $this->indirectObject,
                'attributes' => $this->auditDirtyAttributes,
                'timestamp' => $this->auditTimestamp,
                'agent' => $this->auditAgent,
            ]);
        }
        if ($this->collectBehaviorLogs) {
            $this->owner->trigger(self::EVENT_COLLECT_AUDIT_SAVE);
            $this->owner->trigger(self::EVENT_COLLECT_AUDIT_UPDATE);
        }
        $this->handleAuditSave();
        $this->unmuteAudit();
    }

    /**
     * [[@doctodo method_description:afterInsert]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:afterInsert]] [[@doctodo return_description:afterInsert]]
     */
    public function afterInsert($event)
    {
        if (!$this->owner->isAuditEnabled()) {
            return true;
        }
        if ($this->owner->enableSaveLog && !empty($this->auditDirtyAttributes)) {
            $this->registerAuditEvent([
                'class' => $this->createEventClass,
                'directObject' => $this->directObject,
                'indirectObject' => $this->indirectObject,
                'attributes' => $this->auditDirtyAttributes,
                'timestamp' => $this->auditTimestamp,
                'agent' => $this->auditAgent,
            ]);
        }
        if ($this->collectBehaviorLogs) {
            $this->owner->trigger(self::EVENT_COLLECT_AUDIT_SAVE);
            $this->owner->trigger(self::EVENT_COLLECT_AUDIT_INSERT);
        }
        $this->handleAuditSave();
        $this->unmuteAudit();
    }

    /**
     * [[@doctodo method_description:beforeDelete]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:beforeDelete]] [[@doctodo return_description:beforeDelete]]
     */
    public function beforeDelete($event)
    {
        if (!$this->owner->isAuditEnabled()) {
            return true;
        }
        $this->muteAudit();
        $this->_dirtyAttributes = $this->owner->attributes;
    }

    /**
     * [[@doctodo method_description:afterDelete]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]]
     *
     * @return [[@doctodo return_type:afterDelete]] [[@doctodo return_description:afterDelete]]
     */
    public function afterDelete($event)
    {
        if (!$this->owner->isAuditEnabled()) {
            return true;
        }
        if ($this->collectBehaviorLogs) {
            $this->owner->trigger(self::EVENT_COLLECT_AUDIT_DELETE);
        }
        if ($this->enableDeleteLog) {
            $this->registerAuditEvent([
                'class' => $this->deleteEventClass,
                'directObject' => $this->directObject,
                'indirectObject' => $this->indirectObject,
                'attributes' => $this->auditDirtyAttributes,
                'timestamp' => $this->auditTimestamp,
                'agent' => $this->auditAgent,
            ]);
        }
        $this->handleAuditSave();
        $this->unmuteAudit();
    }

    /**
     * Set ignore attributes.
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
     */
    public function setIgnoreAttributes($value)
    {
        foreach ($value as $field) {
            if (!in_array($field, $this->_ignoreAttributes)) {
                $this->_ignoreAttributes[] = $field;
            }
        }
    }

    /**
     * Get ignore attributes.
     *
     * @return [[@doctodo return_type:getIgnoreAttributes]] [[@doctodo return_description:getIgnoreAttributes]]
     */
    public function getIgnoreAttributes()
    {
        return $this->_ignoreAttributes;
    }

    /**
     * [[@doctodo method_description:isAuditEnabled]].
     *
     * @return [[@doctodo return_type:isAuditEnabled]] [[@doctodo return_description:isAuditEnabled]]
     */
    public function isAuditEnabled()
    {
        if (!$this->enableLogging) {
            return false;
        }
        if (isset(self::$_auditMute)) {
            if (self::$_auditMute === $this) {
                return true;
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * [[@doctodo method_description:muteAudit]].
     *
     * @return [[@doctodo return_type:muteAudit]] [[@doctodo return_description:muteAudit]]
     */
    public function muteAudit()
    {
        if (isset(self::$_auditMute)) {
            if (self::$_auditMute === $this) {
                return true;
            } else {
                return false;
            }
        }
        self::$_auditMute = $this;

        return true;
    }

    /**
     * [[@doctodo method_description:unmuteAudit]].
     *
     * @return [[@doctodo return_type:unmuteAudit]] [[@doctodo return_description:unmuteAudit]]
     */
    public function unmuteAudit()
    {
        if (isset(self::$_auditMute)) {
            if (self::$_auditMute === $this) {
                self::$_auditMute = null;

                return true;
            } else {
                return false;
            }
        }

        return true;
    }
}
