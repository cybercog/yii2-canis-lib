<?php
/**
 * library/db/behaviors/Date.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */

namespace infinite\db\behaviors\auditable;

use Yii;
use yii\base\InvalidConfigException;

class Auditable extends \infinite\db\behaviors\ActiveRecord
{
    public $baseEventClass = 'infinite\\db\\behaviors\\auditable\\BaseEvent';
    public $insertEventClass = 'infinite\\db\\behaviors\\auditable\\InsertEvent';
    public $updateEventClass = 'infinite\\db\\behaviors\\auditable\\UpdateEvent';
    public $deleteEventClass = 'infinite\\db\\behaviors\\auditable\\DeleteEvent';
    public $enableSaveLog = true;
    public $enableDeleteLog = true;
    public $collectBehaviorLogs = true;

    protected $_ignoreAttributes = ['modified', 'created', 'created_by_id', 'modified_by_id'];
    protected $_dirtyAttributes;
    protected $_directObject;
    protected $_indirectObject;
    protected $_auditAgent;
    protected $_auditEvents = [];

    protected static $_auditMute;

    const EVENT_COLLECT_AUDIT_SAVE = 'collectAuditSave';
    const EVENT_COLLECT_AUDIT_INSERT = 'collectAuditInsert';
    const EVENT_COLLECT_AUDIT_UPDATE = 'collectAuditInsert';
    const EVENT_COLLECT_AUDIT_DELETE = 'collectAuditDelete';

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

    public function safeAttributes()
    {
        return ['directObject', 'indirectObject', 'auditAgent'];
    }

    public function getAuditDirtyAttributes()
    {
        return $this->_dirtyAttributes;
    }

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

    public function setAuditAgent($object)
    {
        $this->_auditAgent = $object;
    }

    public function getAuditAgent()
    {
        if (is_null($this->_auditAgent) && isset(Yii::$app->user)) {
            $this->_auditAgent = Yii::$app->user->identity;
        }

        return $this->_auditAgent;
    }

    public function setDirectObject($object)
    {
        $this->_directObject = $object;
    }

    public function getDirectObject()
    {
        if (is_null($this->_directObject)) {
            $this->_directObject = $this->owner;
        }

        return $this->_directObject;
    }

    public function setIndirectObject($object)
    {
        $this->_indirectObject = $object;
    }

    public function getIndirectObject()
    {
        return $this->_indirectObject;
    }

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

    public function beforeDelete($event)
    {
        if (!$this->owner->isAuditEnabled()) { return true; }
        $this->muteAudit();
        $this->_dirtyAttributes = $this->owner->attributes;
    }

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

    public function setIgnoreAttributes($value)
    {
        foreach ($value as $field) {
            if (!in_array($field, $this->_ignoreAttributes)) {
                $this->_ignoreAttributes[] = $field;
            }
        }
    }

    public function getIgnoreAttributes()
    {
        return $this->_ignoreAttributes;
    }

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
