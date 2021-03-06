<?php
namespace canis\action;

use canis\base\Callback;
use canis\base\Exception;
use canis\caching\Cacher;

// use canis\helpers\Console;

/**
 * Interaction [[@doctodo class_description:canis\action\Interaction]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Interaction extends \canis\base\Component // implements \canis\base\InteractionInterface
{
    /**
     * @var [[@doctodo var_type:_id]] [[@doctodo var_description:_id]]
     */
    protected $_id;
    /**
     * @var [[@doctodo var_type:label]] [[@doctodo var_description:label]]
     */
    public $label;
    /**
     * @var [[@doctodo var_type:details]] [[@doctodo var_description:details]]
     */
    public $details;
    /**
     * @var [[@doctodo var_type:inputType]] [[@doctodo var_description:inputType]]
     */
    public $inputType = 'select';
    /**
     * @var [[@doctodo var_type:options]] [[@doctodo var_description:options]]
     */
    public $options = [];
    /**
     * @var [[@doctodo var_type:resolved]] [[@doctodo var_description:resolved]]
     */
    public $resolved = false;
    /**
     * @var [[@doctodo var_type:error]] [[@doctodo var_description:error]]
     */
    public $error = false;
    /**
     * @var [[@doctodo var_type:lastResponse]] [[@doctodo var_description:lastResponse]]
     */
    public $lastResponse = false;
    /**
     * @var [[@doctodo var_type:_callback]] [[@doctodo var_description:_callback]]
     */
    protected $_callback;

    /**
     * Get id.
     *
     * @return [[@doctodo return_type:getId]] [[@doctodo return_description:getId]]
     */
    public function getId()
    {
        if (!isset($this->_id)) {
            $this->_id = md5(uniqid(rand(), true));
        }

        return $this->_id;
    }

    /**
     * [[@doctodo method_description:saveResolution]].
     *
     * @param [[@doctodo param_type:id]]         $id         [[@doctodo param_description:id]]
     * @param [[@doctodo param_type:resolution]] $resolution [[@doctodo param_description:resolution]]
     *
     * @return [[@doctodo return_type:saveResolution]] [[@doctodo return_description:saveResolution]]
     */
    public static function saveResolution($id, $resolution)
    {
        return Cacher::set([__CLASS__, 'resolution', $id], $resolution);
    }

    /**
     * [[@doctodo method_description:attemptResolution]].
     *
     * @return [[@doctodo return_type:attemptResolution]] [[@doctodo return_description:attemptResolution]]
     */
    public function attemptResolution()
    {
        $response = Cacher::get([__CLASS__, 'resolution', $this->id]);
        if ($response !== false && $response !== null) {
            $this->error = false;
            Cacher::set([__CLASS__, 'resolution', $this->id], null);
            if (!$this->resolve($response) || $this->error) {
                $this->lastResponse = microtime(true);
                if (!$this->error) {
                    $this->error = 'Unable to resolve with given response';
                }

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * [[@doctodo method_description:package]].
     *
     * @return [[@doctodo return_type:package]] [[@doctodo return_description:package]]
     */
    public function package()
    {
        $details = $this->details;
        if (is_object($details) || is_array($details)) {
            $details = '<pre>' . print_r($details, true) . '</pre>';
        }

        return [
            'id' => $this->id,
            'hash' => md5($this->id . '-' . $this->lastResponse),
            'label' => $this->label,
            'details' => $details,
            'inputType' => $this->inputType,
            'options' => $this->options,
            'error' => $this->error,
        ];
    }

    /**
     * [[@doctodo method_description:resolve]].
     *
     * @param [[@doctodo param_type:response]] $response [[@doctodo param_description:response]]
     *
     * @return [[@doctodo return_type:resolve]] [[@doctodo return_description:resolve]]
     */
    public function resolve($response)
    {
        // Console::output("Attempting resolve for {$this->id}: $response");
        if ($this->getCallback() === false) {
            //    Console::output("\tNo callback!");
            return false;
        }
        if ($this->getCallback()->call($response)) {
            $this->resolved = true;

            return true;
        }
        // Console::output("\tCallback failed!");
        return false;
    }

    /**
     * Get callback.
     *
     * @return [[@doctodo return_type:getCallback]] [[@doctodo return_description:getCallback]]
     */
    public function getCallback()
    {
        if (!isset($this->_callback)) {
            return false;
        }
        if (is_string($this->_callback)) {
            return Callback::get($this->_callback);
        }

        return $this->_callback;
    }

    /**
     * Set callback.
     *
     * @param [[@doctodo param_type:callback]] $callback [[@doctodo param_description:callback]]
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return [[@doctodo return_type:setCallback]] [[@doctodo return_description:setCallback]]
     *
     */
    public function setCallback($callback)
    {
        if (is_array($callback)) {
            $callback = Callback::set($callback);
        } elseif (is_object($callback) && $callback instanceof Callback) {
            $callback = $callback->id;
        }
        if (!is_string($callback)) {
            throw new Exception("Invalid callback set: " . print_r($callback, true));
        }
        $this->_callback = $callback;

        return $this;
    }
}
