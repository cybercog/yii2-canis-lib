<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\security\role;

use canis\helpers\ArrayHelper;
use Yii;

/**
 * Collector [[@doctodo class_description:canis\security\role\Collector]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Collector extends \canis\base\collector\Collector
{
    /**
     * @var [[@doctodo var_type:_tableRegistry]] [[@doctodo var_description:_tableRegistry]]
     */
    protected $_tableRegistry;
    /**
     * @var [[@doctodo var_type:_initialItems]] [[@doctodo var_description:_initialItems]]
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
     *
     * @param [[@doctodo param_type:value]] $value [[@doctodo param_description:value]]
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
     *
     * @param [[@doctodo param_type:id]] $id [[@doctodo param_description:id]]
     *
     * @return [[@doctodo return_type:getById]] [[@doctodo return_description:getById]]
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
     *
     * @return [[@doctodo return_type:getTableRegistry]] [[@doctodo return_description:getTableRegistry]]
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
