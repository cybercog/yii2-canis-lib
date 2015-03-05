<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\identity\providers;

use infinite\helpers\ArrayHelper;
use Yii;

/**
 * Item [[@doctodo class_description:infinite\security\identity\providers\Item]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Item extends \infinite\base\collector\Item
{
    /**
     * @var [[@doctodo var_type:name]] [[@doctodo var_description:name]]
     */
    public $name;
    /**
     * @var [[@doctodo var_type:handler]] [[@doctodo var_description:handler]]
     */
    public $handler;
    /**
     * @var [[@doctodo var_type:_handlers]] [[@doctodo var_description:_handlers]]
     */
    protected $_handlers = [];
    /**
     * @var [[@doctodo var_type:handlerConfig]] [[@doctodo var_description:handlerConfig]]
     */
    public $handlerConfig = [];
    /**
     * @var [[@doctodo var_type:config]] [[@doctodo var_description:config]]
     */
    public $config = [];

    /**
     * @var [[@doctodo var_type:_creator]] [[@doctodo var_description:_creator]]
     */
    protected $_creator = false;

    /**
     * Get creator priority.
     *
     * @return [[@doctodo return_type:getCreatorPriority]] [[@doctodo return_description:getCreatorPriority]]
     */
    public function getCreatorPriority()
    {
        if ($this->creator) {
            return $this->creator->priority;
        }

        return false;
    }

    /**
     * Get creator.
     *
     * @return [[@doctodo return_type:getCreator]] [[@doctodo return_description:getCreator]]
     */
    public function getCreator()
    {
        if (isset($this->_creator)) {
            return $this->_creator;
        }

        return false;
    }

    /**
     * Set creator.
     *
     * @return [[@doctodo return_type:setCreator]] [[@doctodo return_description:setCreator]]
     */
    public function setCreator($creator)
    {
        if (is_array($creator) && isset($creator['class'])) {
            $creator = Yii::createObject($creator);
        }
        if (!($creator instanceof CreatorInterface)) {
            return;
        }
        $creator->identityProvider = $this;
        $this->_creator = $creator;
    }

    /**
     * Get package.
     *
     * @return [[@doctodo return_type:getPackage]] [[@doctodo return_description:getPackage]]
     */
    public function getPackage()
    {
        return [
            'id' => $this->id,
            'system_id' => $this->object->system_id,
            'label' => $this->name,
        ];
    }

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        if (!isset($this->object)) {
            return false;
        }

        return ArrayHelper::getValue($this->object, 'primaryKey');
    }

    /**
     * @inheritdoc
     */
    public function getSystemId()
    {
        if (parent::getSystemId()) {
            return parent::getSystemId();
        }

        return ArrayHelper::getValue($this->object, 'system_id');
    }

    /**
     * Get handler.
     *
     * @param array $meta [[@doctodo param_description:meta]] [optional]
     *
     * @return [[@doctodo return_type:getHandler]] [[@doctodo return_description:getHandler]]
     */
    public function getHandler($token, $meta = [])
    {
        $key = md5(serialize([$token, $meta]));
        if (!isset($this->_handlers[$key])) {
            $this->_handlers[$key] = false;
            if (isset(Yii::$app->collectors['identityProviders']->handlers[$this->handler])) {
                $handler = Yii::$app->collectors['identityProviders']->handlers[$this->handler];
                foreach ($this->handlerConfig as $key => $value) {
                    $handler[$key] = $value;
                }
                $handler['token'] = $token;
                $handler['meta'] = $meta;
                $handler['config'] = $this->config;
                $this->_handlers[$key] = Yii::createObject($handler);
            }
        }

        return $this->_handlers[$key];
    }
}
