<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\security\identity\providers;

trait HandlerTrait
{
    public $errors = [];
    public $token;
    public $meta = [];
    public $serverMeta = [];
    public $config = [];

    public function validatePassword($user, $password)
    {
        return false;
    }

    public function addError($attribute, $error)
    {
        if (!isset($this->errors[$attribute])) {
            $this->errors[$attribute] = [];
        }
        $this->errors[$attribute][] = $error;
    }
}
