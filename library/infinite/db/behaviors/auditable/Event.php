<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

use Yii;

/**
 * Event [[@doctodo class_description:infinite\db\behaviors\auditable\Event]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Event extends \infinite\base\Component
{
    const EVENT_AUDIT_HOOK = 'auditHook';
    const EVENT_BEFORE_MODEL_SAVE = 'beforeModelSave';
    /**
     * @var [[@doctodo var_type:mergeWith]] [[@doctodo var_description:mergeWith]]
     */
    public $mergeWith = false;
    /**
     * @var [[@doctodo var_type:handleHooksOnCreate]] [[@doctodo var_description:handleHooksOnCreate]]
     */
    public $handleHooksOnCreate = false;
    /**
     * @var [[@doctodo var_type:saveOnRegister]] [[@doctodo var_description:saveOnRegister]]
     */
    public $saveOnRegister = false;
    /**
     * @var [[@doctodo var_type:model]] [[@doctodo var_description:model]]
     */
    public $model;
    /**
     * @var [[@doctodo var_type:context]] [[@doctodo var_description:context]]
     */
    public $context = false;
    /**
     * @var [[@doctodo var_type:_exclusive]] [[@doctodo var_description:_exclusive]]
     */
    protected $_exclusive = false;
    /**
     * @var [[@doctodo var_type:_id]] [[@doctodo var_description:_id]]
     */
    protected $_id;
    /**
     * @var [[@doctodo var_type:_hash]] [[@doctodo var_description:_hash]]
     */
    protected $_hash;
    /**
     * @var [[@doctodo var_type:_agent]] [[@doctodo var_description:_agent]]
     */
    protected $_agent;
    /**
     * @var [[@doctodo var_type:_directObject]] [[@doctodo var_description:_directObject]]
     */
    protected $_directObject;
    /**
     * @var [[@doctodo var_type:_indirectObject]] [[@doctodo var_description:_indirectObject]]
     */
    protected $_indirectObject;
    /**
     * @var [[@doctodo var_type:_tmp]] [[@doctodo var_description:_tmp]]
     */
    protected $_tmp = [];
    /**
     * @var [[@doctodo var_type:_merged]] [[@doctodo var_description:_merged]]
     */
    protected $_merged = [];
    /**
     * @var [[@doctodo var_type:_timestamp]] [[@doctodo var_description:_timestamp]]
     */
    protected $_timestamp;

    /**
     * Prepares object for serialization.
     *
     * @return [[@doctodo return_type:__sleep]] [[@doctodo return_description:__sleep]]
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
     * Set agent.
     *
     * @param [[@doctodo param_type:agent]] $agent [[@doctodo param_description:agent]]
     */
    public function setAgent($agent)
    {
        $this->_agent = $agent;
    }

    /**
     * Get agent.
     *
     * @return [[@doctodo return_type:getAgent]] [[@doctodo return_description:getAgent]]
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
     * Get agent.
     *
     * @return [[@doctodo return_type:getAgentId]] [[@doctodo return_description:getAgentId]]
     */
    public function getAgentId()
    {
        if (is_object($this->_agent)) {
            return $this->_agent->primaryKey;
        }

        return $this->_agent;
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
     * Get indirect object.
     *
     * @return [[@doctodo return_type:getIndirectObjectId]] [[@doctodo return_description:getIndirectObjectId]]
     */
    public function getIndirectObjectId()
    {
        if (is_object($this->_indirectObject)) {
            return $this->_indirectObject->primaryKey;
        }

        return $this->_indirectObject;
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
     * Get direct object.
     *
     * @return [[@doctodo return_type:getDirectObjectId]] [[@doctodo return_description:getDirectObjectId]]
     */
    public function getDirectObjectId()
    {
        if (is_object($this->_directObject)) {
            return $this->_directObject->primaryKey;
        }

        return $this->_directObject;
    }

    /**
     * Set id.
     *
     * @param [[@doctodo param_type:id]] $id [[@doctodo param_description:id]]
     *
     * @return [[@doctodo return_type:setId]] [[@doctodo return_description:setId]]
     */
    public function setId($id)
    {
        return $this->_id = $id;
    }

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Get timestamp.
     *
     * @return [[@doctodo return_type:getTimestamp]] [[@doctodo return_description:getTimestamp]]
     */
    public function getTimestamp()
    {
        $microtime = microtime(true);
        $mtimeParts = explode(".", $microtime);
        if (isset($this->_timestamp)) {
            return $this->_timestamp;
        }
        if (isset($this->model) && isset($this->model->created)) {
            return $this->_timestamp = strtotime($this->model->created) . "." . $mtimeParts[1];
        }

        return $microtime;
    }

    /**
     * Set timestamp.
     *
     * @param [[@doctodo param_type:timestamp]] $timestamp [[@doctodo param_description:timestamp]]
     */
    public function setTimestamp($timestamp)
    {
        $microtime = microtime(true);
        $mtimeParts = explode(".", $microtime);
        if (!isset($mtimeParts[1])) {
            $mtimeParts[1] = 0;
        }
        if (empty($timestamp)) {
            $timestamp = microtime(true);
        }
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp) . "." . $mtimeParts[1];
        }
        $this->_timestamp = $timestamp;
    }

    /**
     * Get hash.
     *
     * @return [[@doctodo return_type:getHash]] [[@doctodo return_description:getHash]]
     */
    public function getHash()
    {
        if (!isset($this->_hash)) {
            $this->_hash = md5(json_encode($this->hashArray));
        }

        return $this->_hash;
    }

    /**
     * Get hash array.
     *
     * @return [[@doctodo return_type:getHashArray]] [[@doctodo return_description:getHashArray]]
     */
    public function getHashArray()
    {
        return [
            'class' => get_class($this),
            'id' => $this->id,
            'directObject' => $this->directObjectId,
            'indirectObject' => $this->indirectObjectId,
        ];
    }

    /**
     * Set exclusive.
     *
     * @param [[@doctodo param_type:exclusive]] $exclusive [[@doctodo param_description:exclusive]]
     *
     * @return [[@doctodo return_type:setExclusive]] [[@doctodo return_description:setExclusive]]
     */
    public function setExclusive($exclusive)
    {
        return $this->_exclusive = $exclusive;
    }

    /**
     * Get exclusive.
     *
     * @return [[@doctodo return_type:getExclusive]] [[@doctodo return_description:getExclusive]]
     */
    public function getExclusive()
    {
        return $this->_exclusive;
    }

    /**
     * [[@doctodo method_description:merge]].
     *
     * @param [[@doctodo param_type:with]] $with [[@doctodo param_description:with]]
     *
     * @return [[@doctodo return_type:merge]] [[@doctodo return_description:merge]]
     */
    public function merge($with)
    {
        $this->_merged[$with->id] = $with;

        return true;
    }

    /**
     * [[@doctodo method_description:isValid]].
     *
     * @return [[@doctodo return_type:isValid]] [[@doctodo return_description:isValid]]
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
     * [[@doctodo method_description:handleHooks]].
     *
     * @return [[@doctodo return_type:handleHooks]] [[@doctodo return_description:handleHooks]]
     */
    public function handleHooks()
    {
        $event = new AuditHookEvent();
        $event->auditEvent = $this;
        $this->trigger(self::EVENT_AUDIT_HOOK, $event);

        return $event->isValid;
    }

    /**
     * [[@doctodo method_description:save]].
     *
     * @return [[@doctodo return_type:save]] [[@doctodo return_description:save]]
     */
    public function save()
    {
        if (!empty($this->_tmp['handled'])) {
            return true;
        }
        $this->_tmp['handled'] = true;
        $auditClass = Yii::$app->classes['Audit'];
        $audit = new $auditClass();
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

    /**
     * Get story.
     *
     * @return [[@doctodo return_type:getStory]] [[@doctodo return_description:getStory]]
     */
    public function getStory()
    {
        return '{{agent}} ' . $this->verb->past . ' {{directObjectType}} {{directObject}}' . $this->indirectStory;
    }

    /**
     * Get indirect story.
     *
     * @return [[@doctodo return_type:getIndirectStory]] [[@doctodo return_description:getIndirectStory]]
     */
    public function getIndirectStory()
    {
        if (empty($this->indirectObject) || !$this->indirectConnector) {
            return '';
        }

        if ($this->context && $this->context === $this->indirectObject->primaryKey) {
            return '';
        }

        return ' ' . $this->indirectConnector . ' {{indirectObject}}';
    }

    /**
     * Get indirect connector.
     *
     * @return [[@doctodo return_type:getIndirectConnector]] [[@doctodo return_description:getIndirectConnector]]
     */
    public function getIndirectConnector()
    {
        return 'to';
    }

    /**
     * Get verb.
     *
     * @return [[@doctodo return_type:getVerb]] [[@doctodo return_description:getVerb]]
     */
    public function getVerb()
    {
        return new \infinite\base\language\Verb('affect');
    }

    /**
     * Get package.
     *
     * @return [[@doctodo return_type:getPackage]] [[@doctodo return_description:getPackage]]
     */
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
