<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\db\behaviors;

use Yii;

use infinite\base\exceptions\Exception;
use infinite\helpers\ArrayHelper;
use infinite\caching\Cacher;

class TagBehavior extends \infinite\db\behaviors\ActiveRecord
{
    public $tagField;
    public $tagClass;
    public $viaClass;
    public $viaLocalField;
    public $viaForeignField;

    protected $_tags;
    protected $_currentTags;
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
    
    // public function __call($name, $params)
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
    //     return parent::__call($name, $params);
    // }

    public function __set($name, $value)
    {
        if ($this->tagField) {
            if ($name === $this->tagField) {
                return call_user_func_array([$this, 'setTags'], [$value]);
            }
        }
        return parent::__set($name, $value);
    }

    public function __get($name)
    {
        if ($this->tagField) {
            if ($name === $this->tagField) {
                return call_user_func_array([$this, 'getTags'], []);
            }
        }
        return parent::__get($name);
    }

    public function hasMethod($name)
    {
        if ($this->isTagBehaviorReady()) {
            if (in_array($name, ['get'.ucfirst($this->tagField), 'set'.ucfirst($this->tagField)])) {
                return true;
            }
        }
        return parent::hasMethod($name);
    }

    public function hasProperty($name, $checkVars = true)
    {
        if ($this->isTagBehaviorReady()) {
            if ($name === $this->tagField) {
                return true;
            }
        }
        return parent::hasProperty($name, $checkVars);
    }

    public function canGetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if ($this->isTagBehaviorReady()) {
            if ($name === $this->tagField) {
                return true;
            }
        }
        
        return parent::canGetProperty($name, $checkVars, $checkBehaviors);
    }

    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if ($this->isTagBehaviorReady()) {
            if ($name === $this->tagField) {
                return true;
            }
        }
        return parent::canSetProperty($name, $checkVars, $checkBehaviors);
    }
    
    public function setTags($tags)
    {
        $this->_tags = $tags;
        $this->_tagsDirty = true;
        $this->_currentTags = null;
    }

    public function getTags()
    {
        if (is_null($this->_tags)) {
            return $this->getCurrentTags();
        }
        return $this->_tags;
    }

    public function getCurrentTags()
    {
        if (!isset($this->_currentTags)) {
            if (!$this->viaClass) {
                throw new \Exception("boom");
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
                $viaTag = new $viaClass;
                $viaTag->attributes = $baseAttributes;
                $viaTag->{$this->viaForeignField} = $tag;
                if (!$viaTag->save()) {
                    throw new \Exception("Unable to save tag: ".print_r($viaTag, true));
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

    public function isTagBehaviorReady()
    {
        return isset($this->tagField, $this->tagClass, $this->viaClass, $this->viaLocalField, $this->viaForeignField);
    }
}
