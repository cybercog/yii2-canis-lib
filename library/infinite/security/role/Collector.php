<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\role;

use Yii;
use infinite\helpers\ArrayHelper;

/**
 * Collector [@doctodo write class description for Collector]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Collector extends \infinite\base\collector\Collector
{
    /**
     * @var __var__tableRegistry_type__ __var__tableRegistry_description__
     */
    protected $_tableRegistry;
    /**
     * @var __var__initialItems_type__ __var__initialItems_description__
     */
    protected $_initialItems = [];
    /**
    * @inheritdoc
    **/
    public function getInitialItems()
    {
        return $this->_initialItems;
    }

    /**
     * Set initial items
     * @param __param_value_type__ $value __param_value_description__
     */
    public function setInitialItems($value)
    {
        $this->_initialItems = $value;
    }

    /**
    * @inheritdoc
    **/
    public function getCollectorItemClass()
    {
        return 'infinite\\security\\role\\Item';
    }

    /**
     * Get by
     * @param __param_id_type__ $id __param_id_description__
     * @return __return_getById_type__ __return_getById_description__
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
     * Get table registry
     * @return __return_getTableRegistry_type__ __return_getTableRegistry_description__
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
    **/
    public function prepareComponent($component)
    {
        if (!Yii::$app->isDbAvailable) {
            return $component;
        }
        if (!isset($component['systemId'])) { return false; }
        $roleClass = Yii::$app->classes['Role'];
        $component['object'] = isset($this->tableRegistry[$component['systemId']]) ? $this->tableRegistry[$component['systemId']] : false;
        if (empty($component['object'])) {
            $component['object'] = new $roleClass;
            $component['object']->name = $component['name'];
            $component['object']->system_id = $component['systemId'];
            if (!$component['object']->save()) {
                throw new Exception("Couldn't save new role {$component['systemId']} ". print_r($component['object']->getFirstErrors(), true));
            }
            $this->_tableRegistry[$component['systemId']] = $component['object'];
            Yii::trace("Role has been initialized {$component['name']} ({$component['systemId']})");
        }

        return $component;
    }

}
