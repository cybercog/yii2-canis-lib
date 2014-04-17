<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use Yii;

/**
 * Event [@doctodo write class description for Event]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
abstract class Event extends \infinite\base\Component
{
    const EVENT_AUDIT_HOOK = 'auditHook';
    public $mergeWith = false;
    public $handleHooksOnCreate = false;
    protected $_exclusive = false;
    protected $_id;
    protected $_hash;
    protected $_agent;
    protected $_directObject;
    protected $_indirectObject;
    protected $_tmp = [];
    protected $_merged = [];

    public function __sleep()
    {
        $keys = array_keys((array) $this);
        $bad = ["\0*\0_tmp"];
        $deobject = ["\0*\0_directObject" => '_directObject', "\0*\0_indirectObject" => '_indirectObject', "\0*\0_agent" => '_agent'];
        foreach ($keys as $k => $key) {
            if (in_array($key, $bad)) {
                unset($keys[$k]);
            } elseif (isset($deobject[$key]) && ($key = $deobject[$key]) && $this->{$key} instanceof \yii\db\ActiveRecord) {
                $this->{$key} = $this->{$key}->primaryKey;
            }
        }

        return $keys;
    }

    public function setAgent($agent)
    {
        $this->_agent = $agent;
    }

    public function getAgent()
    {
        if (is_string($this->_agent) && !is_object($this->_agent)) {
            $registryClass = Yii::$app->classes['Registry'];
            $this->_agent = $registryClass::getObject($this->_agent, false); // no need for access check on agent?
            if (empty($this->_agent)) {
                $this->_agent = false;
            }
        }

        return $this->_agent;
    }

    public function getAgentId()
    {
        if (is_object($this->_agent)) {
            return $this->_agent->primaryKey;
        }

        return $this->_agent;
    }

    public function setIndirectObject($object)
    {
        $this->_indirectObject = $object;
    }

    public function getIndirectObject()
    {
        if (is_string($this->_indirectObject) && !is_object($this->_indirectObject)) {
            $registryClass = Yii::$app->classes['Registry'];
            $this->_indirectObject = $registryClass::getObject($this->_indirectObject);
            if (empty($this->_indirectObject)) {
                $this->_indirectObject = false;
            }
        }

        return $this->_indirectObject;
    }

    public function getIndirectObjectId()
    {
        if (is_object($this->_indirectObject)) {
            return $this->_indirectObject->primaryKey;
        }

        return $this->_indirectObject;
    }

    public function setDirectObject($object)
    {
        $this->_directObject = $object;
    }

    public function getDirectObject()
    {
        if (is_string($this->_directObject) && !is_object($this->_directObject)) {
            $registryClass = Yii::$app->classes['Registry'];
            $this->_directObject = $registryClass::getObject($this->_directObject);
            if (empty($this->_directObject)) {
                $this->_directObject = false;
            }
        }

        return $this->_directObject;
    }

    public function getDirectObjectId()
    {
        if (is_object($this->_directObject)) {
            return $this->_directObject->primaryKey;
        }

        return $this->_directObject;
    }

    public function setId($id)
    {
        return $this->_id = $id;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getHash()
    {
        if (!isset($this->_hash)) {
            $this->_hash = md5(json_encode($this->hashArray));
        }

        return $this->_hash;
    }

    public function getHashArray()
    {
        return [
            'class' => get_class($this),
            'id' => $this->id,
            'directObject' => $this->directObjectId,
            'indirectObject' => $this->indirectObjectId
        ];
    }

    public function setExclusive($exclusive)
    {
        return $this->_exclusive = $exclusive;
    }

    public function getExclusive()
    {
        return $this->_exclusive;
    }

    public function merge($with)
    {
        $this->_merged[$with->id] = $with;

        return true;
    }

    public function isValid()
    {
        if (empty($this->id)) {
            return false;
        }
        if (empty($this->directObject)) {
            return false;
        }
        if (empty($this->agent)) {
            return false;
        }

        return true;
    }

    public function handleHooks()
    {
        $event = new AuditHookEvent;
        $event->auditEvent = $this;
        $this->trigger(self::EVENT_AUDIT_HOOK, $event);

        return $event->isValid;
    }

    public function save()
    {
        $auditClass = Yii::$app->classes['Audit'];
        $audit = new $auditClass;
        $audit->agent_id = $this->agentId;
        $audit->direct_object_id = $this->directObjectId;
        $audit->indirect_object_id = $this->indirectObjectId;
        $audit->event_id = $this->id;
        $audit->hooks_handled = 0;
        if ($this->handleHooksOnCreate && $this->handleHooks()) {
            $audit->hooks_handled = 1;
        }
        $audit->event = serialize($this);

        return $audit->save();
    }
}
