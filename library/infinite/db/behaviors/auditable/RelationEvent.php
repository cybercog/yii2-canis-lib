<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * DeleteEvent [@doctodo write class description for DeleteEvent]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class RelationEvent extends AttributesEvent
{
    /**
     * @var __var_descriptor_type__ __var_descriptor_description__
     */
    public $descriptor;
    /**
     * @inheritdoc
     */
    protected $_id = 'relation';
    /**
     * @var object [[Relation]] model
     */
    protected $_relationObject;

    /**
     * Prepares object for serialization.
     * @return __return___sleep_type__ __return___sleep_description__
     */
    public function __sleep()
    {
        $keys = parent::__sleep();
        $bad = [];
        $deobject = ["\0*\0_relationObject" => '_relationObject'];
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
     * Set the relation object
     * @param object Relation model
     */
    public function setRelationObject($object)
    {
        $this->_relationObject = $object;
    }

    /**
     * Get the relation object
     * @return object Relation model
     */
    public function getRelationObject()
    {
        if (is_string($this->_relationObject) && !is_object($this->_relationObject)) {
            $relationClass = Yii::$app->classes['Relation'];
            $this->_relationObject = $relationClass::get($this->_relationObject);
            if (empty($this->_relationObject)) {
                $this->_relationObject = false;
            }
        }
        return $this->_relationObject;
    }

    /**
     * Get the relation object
     * @return object Relation model
     */
    public function getRelationObjectId()
    {
        if (is_object($this->_relationObject)) {
            return $this->_relationObject->primaryKey;
        }
        return $this->_relationObject;
    }
    
    public function getIndirectConnector()
    {
        return 'and';
    }
}
