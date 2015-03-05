<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web\browser;

use Yii;

/**
 * Response [[@doctodo class_description:infinite\web\browser\Response]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
abstract class Response extends \infinite\base\Object
{
    /**
     * @var [[@doctodo var_type:bundleClass]] [[@doctodo var_description:bundleClass]]
     */
    public $bundleClass = 'infinite\web\browser\Bundle';
    /**
     * @var [[@doctodo var_type:_instances]] [[@doctodo var_description:_instances]]
     */
    protected static $_instances = [];
    /**
     * @var [[@doctodo var_type:_bundles]] [[@doctodo var_description:_bundles]]
     */
    protected $_bundles;

    /**
     * Get instance.
     *
     * @param boolean $static [[@doctodo param_description:static]] [optional]
     *
     * @return [[@doctodo return_type:getInstance]] [[@doctodo return_description:getInstance]]
     */
    public static function getInstance($static = true)
    {
        if ($static) {
            $calledClass = get_called_class();
            if (!isset(static::$_instances[$calledClass])) {
                static::$_instances[$calledClass] = Yii::createObject(['class' => get_called_class()]);
            }

            return static::$_instances[$calledClass];
        } else {
            return Yii::createObject(['class' => get_called_class()]);
        }
    }

    /**
     * [[@doctodo method_description:handleRequests]].
     *
     * @param boolean $handle [[@doctodo param_description:handle]] [optional]
     *
     * @return [[@doctodo return_type:handleRequests]] [[@doctodo return_description:handleRequests]]
     */
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

    /**
     * [[@doctodo method_description:handleInstructions]].
     *
     * @param boolean $handle [[@doctodo param_description:handle]] [optional]
     *
     * @return [[@doctodo return_type:handleInstructions]] [[@doctodo return_description:handleInstructions]]
     */
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

    /**
     * [[@doctodo method_description:package]].
     *
     * @return [[@doctodo return_type:package]] [[@doctodo return_description:package]]
     */
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

    /**
     * [[@doctodo method_description:addBundle]].
     *
     * @return [[@doctodo return_type:addBundle]] [[@doctodo return_description:addBundle]]
     */
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

    /**
     * Get bundles.
     *
     * @return [[@doctodo return_type:getBundles]] [[@doctodo return_description:getBundles]]
     */
    public function getBundles()
    {
        return $this->_bundles;
    }
}
