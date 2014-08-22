<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use Yii;
use yii\helpers\Url;

/**
 * Event [@doctodo write class description for Event]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Event extends \infinite\base\Component
{
    const EVENT_AUDIT_HOOK = 'auditHook';
    const EVENT_BEFORE_MODEL_SAVE= 'beforeModelSave';
    /**
     * @var __var_mergeWith_type__ __var_mergeWith_description__
     */
    public $mergeWith = false;
    /**
     * @var __var_handleHooksOnCreate_type__ __var_handleHooksOnCreate_description__
     */
    public $handleHooksOnCreate = false;
    /**
     * @var __var_handleHooksOnCreate_type__ __var_handleHooksOnCreate_description__
     */
    public $saveOnRegister = false;
    public $model;
    public $context = false;
    /**
     * @var __var__exclusive_type__ __var__exclusive_description__
     */
    protected $_exclusive = false;
    /**
     * @var __var__id_type__ __var__id_description__
     */
    protected $_id;
    /**
     * @var __var__hash_type__ __var__hash_description__
     */
    protected $_hash;
    /**
     * @var __var__agent_type__ __var__agent_description__
     */
    protected $_agent;
    /**
     * @var __var__directObject_type__ __var__directObject_description__
     */
    protected $_directObject;
    /**
     * @var __var__indirectObject_type__ __var__indirectObject_description__
     */
    protected $_indirectObject;
    /**
     * @var __var__tmp_type__ __var__tmp_description__
     */
    protected $_tmp = [];
    /**
     * @var __var__merged_type__ __var__merged_description__
     */
    protected $_merged = [];
    protected $_timestamp;

    /**
     * Prepares object for serialization.
     * @return __return___sleep_type__ __return___sleep_description__
     */
    public function __sleep()
    {
        $keys = array_keys((array) $this);
        $bad = ["\0*\0_tmp", "\0*\0_id", "handleHooksOnCreate", "saveOnRegister", "\0yii\\base\\Component\0_events", "\0yii\\base\\Component\0_behaviors", "\0*\0_memoryId", "\0*\0_backtrace", "watch"];
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

    /**
     * Set agent
     * @param __param_agent_type__ $agent __param_agent_description__
     */
    public function setAgent($agent)
    {
        $this->_agent = $agent;
    }

    /**
     * Get agent
     * @return __return_getAgent_type__ __return_getAgent_description__
     */
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

    /**
     * Get agent
     * @return __return_getAgentId_type__ __return_getAgentId_description__
     */
    public function getAgentId()
    {
        if (is_object($this->_agent)) {
            return $this->_agent->primaryKey;
        }

        return $this->_agent;
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
        if (is_string($this->_indirectObject) && !is_object($this->_indirectObject)) {
            $registryClass = Yii::$app->classes['Registry'];
            $this->_indirectObject = $registryClass::getObject($this->_indirectObject);
            if (empty($this->_indirectObject)) {
                $this->_indirectObject = false;
            }
        }

        return $this->_indirectObject;
    }

    /**
     * Get indirect object
     * @return __return_getIndirectObjectId_type__ __return_getIndirectObjectId_description__
     */
    public function getIndirectObjectId()
    {
        if (is_object($this->_indirectObject)) {
            return $this->_indirectObject->primaryKey;
        }

        return $this->_indirectObject;
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
        if (is_string($this->_directObject) && !is_object($this->_directObject)) {
            $registryClass = Yii::$app->classes['Registry'];
            $this->_directObject = $registryClass::getObject($this->_directObject);
            if (empty($this->_directObject)) {
                $this->_directObject = false;
            }
        }

        return $this->_directObject;
    }

    /**
     * Get direct object
     * @return __return_getDirectObjectId_type__ __return_getDirectObjectId_description__
     */
    public function getDirectObjectId()
    {
        if (is_object($this->_directObject)) {
            return $this->_directObject->primaryKey;
        }

        return $this->_directObject;
    }

    /**
     * Set id
     * @param __param_id_type__ $id __param_id_description__
     * @return __return_setId_type__ __return_setId_description__
     */
    public function setId($id)
    {
        return $this->_id = $id;
    }

    /**
     * Get id
     * @return __return_getId_type__ __return_getId_description__
     */
    public function getId()
    {
        return $this->_id;
    }

    public function getTimestamp()
    {
    	$microtime = microtime(true);
        $mtimeParts = explode(".", $microtime);
        if (isset($this->_timestamp)) {
            return $this->_timestamp;
        }
        if (isset($this->model) && isset($this->model->created)) {
            return $this->_timestamp = strtotime($this->model->created) .".". $mtimeParts[1];
        }
        return $microtime;
    }

    public function setTimestamp($timestamp)
    {
    	$microtime = microtime(true);
        $mtimeParts = explode(".", $microtime);
        if (empty($timestamp)) {
            $timestamp = microtime(true);
        }
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp) .".". $mtimeParts[1];
        }
        $this->_timestamp = $timestamp;
    }

    /**
     * Get hash
     * @return __return_getHash_type__ __return_getHash_description__
     */
    public function getHash()
    {
        if (!isset($this->_hash)) {
            $this->_hash = md5(json_encode($this->hashArray));
        }

        return $this->_hash;
    }

    /**
     * Get hash array
     * @return __return_getHashArray_type__ __return_getHashArray_description__
     */
    public function getHashArray()
    {
        return [
            'class' => get_class($this),
            'id' => $this->id,
            'directObject' => $this->directObjectId,
            'indirectObject' => $this->indirectObjectId
        ];
    }

    /**
     * Set exclusive
     * @param __param_exclusive_type__ $exclusive __param_exclusive_description__
     * @return __return_setExclusive_type__ __return_setExclusive_description__
     */
    public function setExclusive($exclusive)
    {
        return $this->_exclusive = $exclusive;
    }

    /**
     * Get exclusive
     * @return __return_getExclusive_type__ __return_getExclusive_description__
     */
    public function getExclusive()
    {
        return $this->_exclusive;
    }

    /**
     * __method_merge_description__
     * @param __param_with_type__ $with __param_with_description__
     * @return __return_merge_type__ __return_merge_description__
     */
    public function merge($with)
    {
        $this->_merged[$with->id] = $with;

        return true;
    }

    /**
     * __method_isValid_description__
     * @return __return_isValid_type__ __return_isValid_description__
     */
    public function isValid()
    {
        if (empty($this->id)) {
            return false;
        }
        if (empty($this->directObject) && empty($this->indirectObject)) {
            return false;
        }
        if (empty($this->agent)) {
            return false;
        }

        return true;
    }

    /**
     * __method_handleHooks_description__
     * @return __return_handleHooks_type__ __return_handleHooks_description__
     */
    public function handleHooks()
    {
        $event = new AuditHookEvent;
        $event->auditEvent = $this;
        $this->trigger(self::EVENT_AUDIT_HOOK, $event);

        return $event->isValid;
    }

    /**
     * __method_save_description__
     * @return __return_save_type__ __return_save_description__
     */
    public function save()
    {
        if (!empty($this->_tmp['handled'])) {
            return true;
        }
        $this->_tmp['handled'] = true;
        $auditClass = Yii::$app->classes['Audit'];
        $audit = new $auditClass;
        $audit->agent_id = $this->agentId;
        $audit->direct_object_id = $this->directObjectId;
        $audit->indirect_object_id = $this->indirectObjectId;
        $audit->event_id = $this->id;
        $audit->hooks_handled = 0;
        $audit->created = date("Y-m-d G:i:s", $this->timestamp);
        $this->trigger(self::EVENT_BEFORE_MODEL_SAVE, new EventEvent(['model' => $audit]));
        if ($this->handleHooksOnCreate && $this->handleHooks()) {
            $audit->hooks_handled = 1;
        }
        $audit->event = serialize($this);
        if (!$audit->save()) {
            return false;
        }
        return $audit;
    }

    public function getStory()
    {
        return '{{agent}} '. $this->verb->past .' {{directObjectType}} {{directObject}}' . $this->indirectStory;
    }

    public function getIndirectStory()
    {
        if (empty($this->indirectObject) || !$this->indirectConnector) {
            return '';
        }

        if ($this->context && $this->context === $this->indirectObject->primaryKey) {
            return '';
        }
        return ' '. $this->indirectConnector .' {{indirectObject}}';
    }

    public function getIndirectConnector()
    {
        return 'to';
    }

    public function getVerb()
    {
        return new \infinite\base\language\Verb('affect');
    }

    public function getPackage()
    {
        $package = ['key' => null, 'story' => $this->story, 'details' => null, 'objects' => [], 'primaryObject' => null, 'agent' => null, 'timestamp' => $this->timestamp];
        $objectKeys = [get_class($this)];
        $replace = [];
        if ($this->indirectObject) {
            $package['primaryObject'] = $this->indirectObject->primaryKey;
            $package['objects']['indirectObject'] = $this->indirectObject;
            $keys = [$this->indirectObject->primaryKey];
            $objectKeys[] = $this->indirectObject->primaryKey;
            if ($this->directObject) {
                $keys[] = $this->directObject->primaryKey;
            }
            $replace['{{indirectObject}}'] = '{{' . implode(':', $keys) . '}}';
            $replace['{{indirectObjectType}}'] = $this->indirectObject->getHumanType();
        } else {
            $objectKeys[] = null;
        }
        if ($this->directObject) {
            $package['primaryObject'] = $this->directObject->primaryKey;
            $package['objects']['directObject'] = $this->directObject;
            $keys = [$this->directObject->primaryKey];
            $objectKeys[] = $this->directObject->primaryKey;
            if ($this->indirectObject) {
                $keys[] = $this->indirectObject->primaryKey;
            }
            $replace['{{directObject}}'] = '{{' . implode(':', $keys) . '}}';
            $replace['{{directObjectType}}'] = $this->directObject->getHumanType();
        } else {
            $objectKeys[] = null;
        }
        if ($this->agent) {
            $package['agent'] = $this->agent->primaryKey;
            $package['objects']['agent'] = $this->agent;
            $objectKeys[] = $this->agent->primaryKey;
            $replace['{{agent}}'] = '{{' . $this->agent->primaryKey . '}}';
        } else {
            $objectKeys[] = null;
        }
        $package['key'] = md5(serialize($objectKeys));
        $package['story'] = strtr($package['story'], $replace);
        return $package;
    }
}
