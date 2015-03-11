<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\security\identity\providers;

use teal\base\exceptions\Exception;
use teal\helpers\ArrayHelper;
use Yii;

/**
 * Collector [[@doctodo class_description:teal\security\identity\providers\Collector]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Collector extends \teal\base\collector\Collector
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
     * @var [[@doctodo var_type:_handlers]] [[@doctodo var_description:_handlers]]
     */
    protected $_handlers = [];

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
     * Get handlers.
     *
     * @return [[@doctodo return_type:getHandlers]] [[@doctodo return_description:getHandlers]]
     */
    public function getHandlers()
    {
        return $this->_handlers;
    }

    /**
     * Set handlers.
     *
     * @param [[@doctodo param_type:values]] $values [[@doctodo param_description:values]]
     */
    public function setHandlers($values)
    {
        foreach ($values as $key => $value) {
            $this->registerHandler($key, $value);
        }
    }

    /**
     * [[@doctodo method_description:registerHandler]].
     *
     * @param [[@doctodo param_type:key]]     $key     [[@doctodo param_description:key]]
     * @param [[@doctodo param_type:handler]] $handler [[@doctodo param_description:handler]]
     */
    public function registerHandler($key, $handler)
    {
        if (!is_array($handler)) {
            $handler = [
                'class' => $handler,
            ];
        }
        $this->_handlers[$key] = $handler;
    }

    /**
     * @inheritdoc
     */
    public function getCollectorItemClass()
    {
        return Item::className();
    }

    /**
     * [[@doctodo method_description:attemptCreate]].
     *
     * @param [[@doctodo param_type:username]] $username [[@doctodo param_description:username]]
     * @param [[@doctodo param_type:password]] $password [[@doctodo param_description:password]]
     *
     * @return [[@doctodo return_type:attemptCreate]] [[@doctodo return_description:attemptCreate]]
     */
    public function attemptCreate($username, $password)
    {
        $creators = [];
        foreach ($this->getAll() as $identityProvider) {
            if ($identityProvider->getCreatorPriority() !== false) {
                $creators[] = [
                    'priority' => sprintf("%1$010d", $identityProvider->getCreatorPriority()) . '---' . md5($identityProvider->systemId),
                    'provider' => $identityProvider,
                ];
            }
        }
        ArrayHelper::multisort($creators, 'priority', SORT_DESC);
        foreach ($creators as $creator) {
            //\d($creator['provider']->creator);
            $user = $creator['provider']->creator->attemptCreate($username, $password);
            if ($user) {
                $userClass = Yii::$app->classes['User'];
                $user = $userClass::get($user->primaryKey, false);

                return $user;
            }
        }

        return false;
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
            $roleClass = Yii::$app->classes['IdentityProvider'];
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
        Yii::beginProfile('Component:::identityProviders::prepare');
        if (!isset($component['systemId'])) {
            return false;
        }
        $roleClass = Yii::$app->classes['IdentityProvider'];
        $component['object'] = isset($this->tableRegistry[$component['systemId']]) ? $this->tableRegistry[$component['systemId']] : false;
        if (empty($component['object'])) {
            $component['object'] = new $roleClass();
            $component['object']->name = $component['name'];
            $component['object']->system_id = $component['systemId'];
            $component['object']->handler = $component['handler'];
            if (!$component['object']->save()) {
                throw new Exception("Couldn't save new identity provider {$component['systemId']} " . print_r($component['object']->getFirstErrors(), true));
            }
            $this->_tableRegistry[$component['systemId']] = $component['object'];
            Yii::trace("Identity Provider has been initialized {$component['name']} ({$component['systemId']})");
        }
        Yii::endProfile('Component:::identityProviders::prepare');

        return $component;
    }
}
