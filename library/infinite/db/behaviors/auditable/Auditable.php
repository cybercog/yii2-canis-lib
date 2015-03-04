<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use infinite\caching\Cacher;
use Yii;
use yii\base\InvalidConfigException;

/**
 * Auditable [@doctodo write class description for Auditable].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Auditable extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var int What is defined as recent? Used when ignoring relation saves
     */
    const RECENT_IN_SECONDS = 300;
    /**
     * @var string Audit event base class
     */
    public $baseEventClass = 'infinite\db\behaviors\auditable\BaseEvent';
    /**
     * @var string Audit insert event class
     */
    public $createEventClass = 'infinite\db\behaviors\auditable\CreateEvent';
    /**
     * @var string Audit uodate event class
     */
    public $updateEventClass = 'infinite\db\behaviors\auditable\UpdateEvent';
    /**
     * @var string Audit delete event class
     */
    public $deleteEventClass = 'infinite\db\behaviors\auditable\DeleteEvent';
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
            \infinite\db\ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',

            \infinite\db\ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
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
     */
    public function getAuditDirtyAttributes()
    {
        return $this->_dirtyAttributes;
    }

    public function suppressAudit()
    {
        $this->_enableLogging = false;

        return $this->owner;
    }

    public function enableLogging()
    {
        $this->_enableLogging = true;

        return $this->owner;
    }

    public function setEnableLogging($value)
    {
        $this->_enableLogging = $value;

        return $this->owner;
    }

    public function getEnableLogging()
    {
        return $this->_enableLogging;
    }

    public function behaviors()
    {
        return [];
    }

    public function prepareEventObject($event)
    {
        return $event;
    }

    /**
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
     *
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

    public function getRecentEvent($eventId = 'any')
    {
        if (empty($this->owner->primaryKey)) {
            return false;
        }

        return Cacher::get(['eventSave', $eventId, $this->owner->primaryKey]);
    }

    /**
     * Set audit agent.
     */
    public function setAuditAgent($object)
    {
        $this->_auditAgent = $object;
    }

    /**
     * Set audit agent.
     */
    public function setAuditTimestamp($audit)
    {
        $this->_auditTimestamp = $audit;
    }

    public function getAuditTimestamp()
    {
        return $this->_auditTimestamp;
    }

    /**
     * Get audit agent.
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
     */
    public function setDirectObject($object)
    {
        $this->_directObject = $object;
    }

    /**
     * Get direct object.
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
     */
    public function setIndirectObject($object)
    {
        $this->_indirectObject = $object;
    }

    /**
     * Get indirect object.
     */
    public function getIndirectObject()
    {
        return $this->_indirectObject;
    }

    /**
     *
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
     *
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
     *
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
     *
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
     *
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
     */
    public function getIgnoreAttributes()
    {
        return $this->_ignoreAttributes;
    }

    /**
     *
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
     *
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
     *
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
