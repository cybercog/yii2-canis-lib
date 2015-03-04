<?php
namespace infinite\action;

use infinite\base\Callback;
use infinite\base\Exception;
use infinite\caching\Cacher;

// use infinite\helpers\Console;

/**
 * Status [@doctodo write class description for Status].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Interaction extends \infinite\base\Component // implements \infinite\base\InteractionInterface
{
    protected $_id;
    public $label;
    public $details;
    public $inputType = 'select';
    public $options = [];
    public $resolved = false;
    public $error = false;
    public $lastResponse = false;
    protected $_callback;

    public function getId()
    {
        if (!isset($this->_id)) {
            $this->_id = md5(uniqid(rand(), true));
        }

        return $this->_id;
    }

    public static function saveResolution($id, $resolution)
    {
        return Cacher::set([__CLASS__, 'resolution', $id], $resolution);
    }

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
