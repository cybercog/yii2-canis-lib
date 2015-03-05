<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\role;

use infinite\helpers\ArrayHelper;
use Yii;

/**
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Collector extends \infinite\base\collector\Collector
{
    /**
     */
    protected $_tableRegistry;
    /**
     */
    protected $_initialItems = [];
    /**
     * @inheritdoc
     */
    public function getInitialItems()
    {
        return $this->_initialItems;
    }

    /**
     * Set initial items.
     */
    public function setInitialItems($value)
    {
        $this->_initialItems = $value;
    }

    /**
     * @inheritdoc
     */
    public function getCollectorItemClass()
    {
        return Item::className();
    }

    /**
     * Get by.
     */
    public function getById($id)
    {
        foreach ($this->tableRegistry as $role) {
            if ($role->primaryKey === $id) {
                $object = $this->getOne($role->system_id);
                if (isset($object->object)) {
                    return $object;
                }
                break;
            }
        }

        return false;
    }

    /**
     * Get table registry.
     */
    public function getTableRegistry()
    {
        if (is_null($this->_tableRegistry)) {
            $roleClass = Yii::$app->classes['Role'];
            $this->_tableRegistry = [];
            if ($roleClass::tableExists()) {
                $om = $roleClass::find()->all();
                $this->_tableRegistry = ArrayHelper::index($om, 'system_id');
            }
        }

        return $this->_tableRegistry;
    }

    /**
     * @inheritdoc
     */
    public function prepareComponent($component)
    {
        if (!Yii::$app->isDbAvailable) {
            return $component;
        }
        Yii::beginProfile('Component:::role::prepare');
        if (!isset($component['systemId'])) {
            return false;
        }
        $roleClass = Yii::$app->classes['Role'];
        $component['object'] = isset($this->tableRegistry[$component['systemId']]) ? $this->tableRegistry[$component['systemId']] : false;
        if (empty($component['object'])) {
            $component['object'] = new $roleClass();
            $component['object']->name = $component['name'];
            $component['object']->system_id = $component['systemId'];
            if (!$component['object']->save()) {
                throw new Exception("Couldn't save new role {$component['systemId']} " . print_r($component['object']->getFirstErrors(), true));
            }
            $this->_tableRegistry[$component['systemId']] = $component['object'];
            Yii::trace("Role has been initialized {$component['name']} ({$component['systemId']})");
        }
        Yii::endProfile('Component:::role::prepare');

        return $component;
    }
}
