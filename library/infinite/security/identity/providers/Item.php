<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\identity\providers;

use Yii;
use infinite\helpers\ArrayHelper;

/**
 * Item [@doctodo write class description for Item]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Item extends \infinite\base\collector\Item
{
    /**
     * @var __var_name_type__ __var_name_description__
     */
    public $name;
    public $handler;
    protected $_handlers = [];
    public $handlerConfig = [];
    public $config = [];

    protected $_creator = false;

    public function getCreatorPriority()
    {
        if ($this->creator) {
            return $this->creator->priority;
        }
        return false;
    }

    public function getCreator()
    {
        if (isset($this->_creator)) {
            return $this->_creator;
        }
        return false;
    }

    public function setCreator($creator)
    {
        if (is_array($creator) && isset($creator['class'])) {
            $creator = Yii::createObject($creator);
        }
        if (!($creator instanceof CreatorInterface)){ 
            return;
        }
        $creator->identityProvider = $this;
        $this->_creator = $creator;
    }
    
    /**
     * Get package
     * @return __return_getPackage_type__ __return_getPackage_description__
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
     * Get id
     * @return __return_getId_type__ __return_getId_description__
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
