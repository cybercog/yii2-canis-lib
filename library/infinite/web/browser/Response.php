<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

use Yii;

/**
 * Response [@doctodo write class description for Response]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
abstract class Response extends \infinite\base\Object
{
    public $bundleClass = 'infinite\\web\\browser\\Bundle';
    protected static $_instance;
    protected $_bundles;

    public static function getInstance($static = true)
    {
        if ($static) {
            if (!isset(static::$_instance)) {
                static::$_instance = Yii::createObject(['class' => get_called_class()]);
            }

            return static::$_instance;
        } else {
            return Yii::createObject(['class' => get_called_class()]);
        }
    }

    public static function handleRequests($requests, $baseInstructions, $handle = true)
    {
        $response = static::getInstance();
        foreach ($requests as $requestId => $instructions) {
            if (isset($response->bundles[$requestId])) {
                continue;
            }
            $instructions = array_merge(['id' => $requestId], $baseInstructions, $instructions);
            $instructionBundle = $response->addBundle(['instructions' => $instructions]);
            if ($handle) {
                $instructionBundle->handle();
            }
        }

        return $response;
    }

    public static function handleInstructions($instructions, $handle = true)
    {
        $instance = static::getInstance();
        $bundleClass = $instance->bundleClass;
        $bundle = Yii::createObject(['class' => $bundleClass, 'instructions' => $instructions]);
        if ($handle) {
            $bundle->handle();
        }

        return $bundle;
    }

    public function package()
    {
        $package = [];
        $package['responses'] = [];
        if (!empty($this->_bundles)) {
            foreach ($this->_bundles as $bundleId => $bundle) {
                if (is_object($bundle)) {
                    $bundle = $bundle->package();
                }
                $package['responses'][$bundleId] = $bundle;
            }
        }

        return $package;
    }

    public function addBundle($bundle)
    {
        if (!isset($this->_bundles)) {
            $this->_bundles = [];
        }
        if (!is_array($bundle)) {
            $bundle = [];
        }
        if (!isset($bundle['class'])) {
            $bundle['class'] = $this->bundleClass;
        }
        $bundle = Yii::createObject($bundle);
        $this->_bundles[$bundle->id] = $bundle;

        return $bundle;
    }

    public function getBundles()
    {
        return $this->_bundles;
    }
}
