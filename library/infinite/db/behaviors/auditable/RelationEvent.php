<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors\auditable;

/**
 * RelationEvent [[@doctodo class_description:infinite\db\behaviors\auditable\RelationEvent]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class RelationEvent extends AttributesEvent
{
    /**
     * @var [[@doctodo var_type:descriptor]] [[@doctodo var_description:descriptor]]
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
     * @inheritdoc
     */
    public $saveOnRegister = true;

    /**
     * Prepares object for serialization.
     *
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
     * Set the relation object.
     */
    public function setRelationObject($object)
    {
        $this->_relationObject = $object;
    }

    /**
     * Get the relation object.
     *
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
     * Get the relation object.
     *
     * @return object Relation model
     */
    public function getRelationObjectId()
    {
        if (is_object($this->_relationObject)) {
            return $this->_relationObject->primaryKey;
        }

        return $this->_relationObject;
    }

    /**
     * @inheritdoc
     */
    public function getIndirectConnector()
    {
        return 'and';
    }
}
