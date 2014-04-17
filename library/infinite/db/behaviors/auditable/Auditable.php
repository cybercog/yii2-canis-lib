<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use Yii;
use yii\base\InvalidConfigException;

/**
 * Auditable [@doctodo write class description for Auditable]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Auditable extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var string Audit event base class
     */
    public $baseEventClass = 'infinite\\db\\behaviors\\auditable\\BaseEvent';
    /**
     * @var string Audit insert event class 
     */
    public $insertEventClass = 'infinite\\db\\behaviors\\auditable\\InsertEvent';
    /**
     * @var string Audit uodate event class 
     */
    public $updateEventClass = 'infinite\\db\\behaviors\\auditable\\UpdateEvent';
    /**
     * @var string Audit delete event class 
     */
    public $deleteEventClass = 'infinite\\db\\behaviors\\auditable\\DeleteEvent';
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

            \infinite\db\ActiveRecord::EVENT_BEFORE_DELETE=> 'beforeDelete',
            \infinite\db\ActiveRecord::EVENT_AFTER_DELETE=> 'afterDelete'
        ];
    }

    /**
    * @inheritdoc
    */
    public function safeAttributes()
    {
        return ['directObject', 'indirectObject', 'auditAgent'];
    }

    /**
     * Get audit dirty attributes
     * @return __return_getAuditDirtyAttributes_type__ __return_getAuditDirtyAttributes_description__
     */
    public function getAuditDirtyAttributes()
    {
        return $this->_dirtyAttributes;
    }

    /**
     * __method_registerEvent_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_registerEvent_type__ __return_registerEvent_description__
     * @throws InvalidConfigException __exception_InvalidConfigException_description__
     */
    public function registerEvent($event)
    {
        if (is_array($event)) {
            if (!isset($event['class'])) {
                $event['class'] = $this->baseEventClass;
            }
            $event = Yii::createObject($event);
        }
        if (!($event instanceof Event)) {
            throw new InvalidConfigException("Invalid audit event thrown");
        }
        if ($event->isValid()) {
            if (!isset($this->_auditEvents)) {
                $this->_auditEvents = [];
            }
            $this->_auditEvents[$event->id] = $event;

            return $event;
        }

        return false;
    }

    /**
     * __method_handleAuditSave_description__
     * @return __return_handleAuditSave_type__ __return_handleAuditSave_description__
     */
    public function handleAuditSave()
    {
        if (!$this->owner->isAuditEnabled() || empty($this->_auditEvents)) { return true; }
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
            $event->save();
        }
        $this->_auditEvents = null;

        return true;
    }

    /**
     * Set audit agent
     * @param __param_object_type__ $object __param_object_description__
     */
    public function setAuditAgent($object)
    {
        $this->_auditAgent = $object;
    }

    /**
     * Get audit agent
     * @return __return_getAuditAgent_type__ __return_getAuditAgent_description__
     */
    public function getAuditAgent()
    {
        if (is_null($this->_auditAgent) && isset(Yii::$app->user)) {
            $this->_auditAgent = Yii::$app->user->identity;
        }

        return $this->_auditAgent;
    }

    /**
     * Set direct object
     * @param __param_object_type__ $object __param_object_description__
     */
    public function setDirectObject($object)
    {
        $this->_directObject = $object;
    }

    /**
     * Get direct object
     * @return __return_getDirectObject_type__ __return_getDirectObject_description__
     */
    public function getDirectObject()
    {
        if (is_null($this->_directObject)) {
            $this->_directObject = $this->owner;
        }

        return $this->_directObject;
    }

    /**
     * Set indirect object
     * @param __param_object_type__ $object __param_object_description__
     */
    public function setIndirectObject($object)
    {
        $this->_indirectObject = $object;
    }

    /**
     * Get indirect object
     * @return __return_getIndirectObject_type__ __return_getIndirectObject_description__
     */
    public function getIndirectObject()
    {
        return $this->_indirectObject;
    }

    /**
     * __method_beforeSave_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_beforeSave_type__ __return_beforeSave_description__
     */
    public function beforeSave($event)
    {
        if (!$this->owner->isAuditEnabled()) { return true; }
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
     * __method_afterUpdate_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_afterUpdate_type__ __return_afterUpdate_description__
     */
    public function afterUpdate($event)
    {
        if (!$this->owner->isAuditEnabled()) { return true; }
        if ($this->owner->enableSaveLog && !empty($this->auditDirtyAttributes)) {
            $this->registerEvent([
                'class' => $this->updateEventClass,
                'directObject' => $this->directObject,
                'indirectObject' => $this->indirectObject,
                'attributes' => $this->auditDirtyAttributes,
                'agent' => $this->auditAgent
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
     * __method_afterInsert_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_afterInsert_type__ __return_afterInsert_description__
     */
    public function afterInsert($event)
    {
        if (!$this->owner->isAuditEnabled()) { return true; }
        if ($this->owner->enableSaveLog && !empty($this->auditDirtyAttributes)) {
            $this->registerEvent([
                'class' => $this->insertEventClass,
                'directObject' => $this->directObject,
                'indirectObject' => $this->indirectObject,
                'attributes' => $this->auditDirtyAttributes,
                'agent' => $this->auditAgent
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
     * __method_beforeDelete_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_beforeDelete_type__ __return_beforeDelete_description__
     */
    public function beforeDelete($event)
    {
        if (!$this->owner->isAuditEnabled()) { return true; }
        $this->muteAudit();
        $this->_dirtyAttributes = $this->owner->attributes;
    }

    /**
     * __method_afterDelete_description__
     * @param __param_event_type__ $event __param_event_description__
     * @return __return_afterDelete_type__ __return_afterDelete_description__
     */
    public function afterDelete($event)
    {
        if (!$this->owner->isAuditEnabled()) { return true; }
        if ($this->collectBehaviorLogs) {
            $this->owner->trigger(self::EVENT_COLLECT_AUDIT_DELETE);
        }
        if ($this->enableDeleteLog) {
            $this->registerEvent([
                'class' => $this->deleteEventClass,
                'directObject' => $this->directObject,
                'indirectObject' => $this->indirectObject,
                'attributes' => $this->auditDirtyAttributes,
                'agent' => $this->auditAgent
            ]);
        }
        $this->handleAuditSave();
        $this->unmuteAudit();
    }

    /**
     * Set ignore attributes
     * @param __param_value_type__ $value __param_value_description__
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
     * Get ignore attributes
     * @return __return_getIgnoreAttributes_type__ __return_getIgnoreAttributes_description__
     */
    public function getIgnoreAttributes()
    {
        return $this->_ignoreAttributes;
    }

    /**
     * __method_isAuditEnabled_description__
     * @return __return_isAuditEnabled_type__ __return_isAuditEnabled_description__
     */
    public function isAuditEnabled()
    {
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
     * __method_muteAudit_description__
     * @return __return_muteAudit_type__ __return_muteAudit_description__
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
     * __method_unmuteAudit_description__
     * @return __return_unmuteAudit_type__ __return_unmuteAudit_description__
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
