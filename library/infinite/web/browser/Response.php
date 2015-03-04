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
 */
abstract class Response extends \infinite\base\Object
{
    /**
     * @var __var_bundleClass_type__ __var_bundleClass_description__
     */
    public $bundleClass = 'infinite\web\browser\Bundle';
    /**
     * @var __var__instance_type__ __var__instance_description__
     */
    protected static $_instances = [];
    /**
     * @var __var__bundles_type__ __var__bundles_description__
     */
    protected $_bundles;

    /**
     * Get instance
     * @param boolean $static __param_static_description__ [optional]
     * @return __return_getInstance_type__ __return_getInstance_description__
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
     * __method_handleRequests_description__
     * @param __param_requests_type__ $requests __param_requests_description__
     * @param __param_baseInstructions_type__ $baseInstructions __param_baseInstructions_description__
     * @param boolean $handle __param_handle_description__ [optional]
     * @return __return_handleRequests_type__ __return_handleRequests_description__
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
     * __method_handleInstructions_description__
     * @param __param_instructions_type__ $instructions __param_instructions_description__
     * @param boolean $handle __param_handle_description__ [optional]
     * @return __return_handleInstructions_type__ __return_handleInstructions_description__
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
     * __method_package_description__
     * @return __return_package_type__ __return_package_description__
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
     * __method_addBundle_description__
     * @param __param_bundle_type__ $bundle __param_bundle_description__
     * @return __return_addBundle_type__ __return_addBundle_description__
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
     * Get bundles
     * @return __return_getBundles_type__ __return_getBundles_description__
     */
    public function getBundles()
    {
        return $this->_bundles;
    }
}
