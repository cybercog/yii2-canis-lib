<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use infinite\base\exceptions\Exception;

/**
 * TagBehavior [[@doctodo class_description:infinite\db\behaviors\TagBehavior]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class TagBehavior extends \infinite\db\behaviors\ActiveRecord
{
    /**
     * @var [[@doctodo var_type:tagField]] [[@doctodo var_description:tagField]]
     */
    public $tagField;
    /**
     * @var [[@doctodo var_type:tagClass]] [[@doctodo var_description:tagClass]]
     */
    public $tagClass;
    /**
     * @var [[@doctodo var_type:viaClass]] [[@doctodo var_description:viaClass]]
     */
    public $viaClass;
    /**
     * @var [[@doctodo var_type:viaLocalField]] [[@doctodo var_description:viaLocalField]]
     */
    public $viaLocalField;
    /**
     * @var [[@doctodo var_type:viaForeignField]] [[@doctodo var_description:viaForeignField]]
     */
    public $viaForeignField;

    /**
     * @var [[@doctodo var_type:_tags]] [[@doctodo var_description:_tags]]
     */
    protected $_tags;
    /**
     * @var [[@doctodo var_type:_currentTags]] [[@doctodo var_description:_currentTags]]
     */
    protected $_currentTags;
    /**
     * @var [[@doctodo var_type:_tagsDirty]] [[@doctodo var_description:_tagsDirty]]
     */
    protected $_tagsDirty = false;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            \infinite\db\ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            \infinite\db\ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
        ];
    }

    // {
    //     if ($this->tagField) {
    //         $getter = 'get'.ucfirst($this->tagField);
    //         $setter = 'set'.ucfirst($this->tagField);
    //         if ($name === $getter) {
    //             return call_user_func_array([$this, 'getTags'], $params);
    //         } elseif ($name === $setter) {
    //             return call_user_func_array([$this, 'setTags'], $params);
    //         }
    //     }
    // }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($this->tagField) {
            if ($name === $this->tagField) {
                return call_user_func_array([$this, 'setTags'], [$value]);
            }
        }

        return parent::__set($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($this->tagField) {
            if ($name === $this->tagField) {
                return call_user_func_array([$this, 'getTags'], []);
            }
        }

        return parent::__get($name);
    }

    /**
     * @inheritdoc
     */
    public function hasMethod($name)
    {
        if ($this->isTagBehaviorReady()) {
            if (in_array($name, ['get' . ucfirst($this->tagField), 'set' . ucfirst($this->tagField)])) {
                return true;
            }
        }

        return parent::hasMethod($name);
    }

    /**
     * @inheritdoc
     */
    public function hasProperty($name, $checkVars = true)
    {
        if ($this->isTagBehaviorReady()) {
            if ($name === $this->tagField) {
                return true;
            }
        }

        return parent::hasProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if ($this->isTagBehaviorReady()) {
            if ($name === $this->tagField) {
                return true;
            }
        }

        return parent::canGetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if ($this->isTagBehaviorReady()) {
            if ($name === $this->tagField) {
                return true;
            }
        }

        return parent::canSetProperty($name, $checkVars, $checkBehaviors);
    }

    /**
     * Set tags.
     */
    public function setTags($tags)
    {
        $this->_tags = $tags;
        $this->_tagsDirty = true;
        $this->_currentTags = null;
    }

    /**
     * Get tags.
     *
     * @return [[@doctodo return_type:getTags]] [[@doctodo return_description:getTags]]
     */
    public function getTags()
    {
        if (is_null($this->_tags)) {
            return $this->getCurrentTags();
        }

        return $this->_tags;
    }

    /**
     * Get current tags.
     *
     * @throws \ [[@doctodo exception_description:\]]
     * @return [[@doctodo return_type:getCurrentTags]] [[@doctodo return_description:getCurrentTags]]
     *
     */
    public function getCurrentTags()
    {
        if (!isset($this->_currentTags)) {
            if (!$this->viaClass) {
                return [];
            }
            $viaClass = $this->viaClass;
            $params = [$this->viaLocalField => $this->owner->primaryKey];
            $rawTags = $viaClass::find()->disableAccessCheck()->where($params)->select($this->viaForeignField)->column();

            $this->_currentTags = [];
            foreach ($rawTags as $tag) {
                $this->_currentTags[$tag] = $tag;
            }
        }

        return $this->_currentTags;
    }

    /**
     * @inheritdoc
     */
    public function safeAttributes()
    {
        if ($this->isTagBehaviorReady()) {
            return [$this->tagField];
        }

        return [];
    }

    /**
     * [[@doctodo method_description:afterSave]].
     *
     * @throws \         [[@doctodo exception_description:\]]
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return [[@doctodo return_type:afterSave]] [[@doctodo return_description:afterSave]]
     *
     */
    public function afterSave($event)
    {
        if (!$this->isTagBehaviorReady()) {
            return;
        }
        if (!$this->_tagsDirty) {
            return;
        }

        $baseAttributes = [$this->viaLocalField => $this->owner->primaryKey];
        $currentTags = $this->getCurrentTags();
        if (empty($this->_tags)) {
            $this->_tags = [];
        }
        $viaClass = $this->viaClass;
        foreach ($this->_tags as $tag) {
            if (is_object($tag)) {
                $tag = $tag->primaryKey;
            }
            if (!isset($currentTags[$tag])) {
                $viaTag = new $viaClass();
                $viaTag->attributes = $baseAttributes;
                $viaTag->{$this->viaForeignField} = $tag;
                if (!$viaTag->save()) {
                    throw new Exception("Unable to save tag: " . print_r($viaTag, true));
                }
            }
            unset($currentTags[$tag]);
        }
        if (!empty($currentTags)) {
            $currentTags = $viaClass::find()->disableAccessCheck()->where([$this->viaForeignField => array_values($currentTags), $this->viaLocalField => $this->owner->primaryKey])->all();
            foreach ($currentTags as $tag) {
                $tag->delete();
            }
        }
        $this->_currentTags = null;
        $this->_tagsDirty = false;
        $this->_tags = null;
    }

    /**
     * [[@doctodo method_description:isTagBehaviorReady]].
     *
     * @return [[@doctodo return_type:isTagBehaviorReady]] [[@doctodo return_description:isTagBehaviorReady]]
     */
    public function isTagBehaviorReady()
    {
        return isset($this->tagField, $this->tagClass, $this->viaClass, $this->viaLocalField, $this->viaForeignField);
    }
}
