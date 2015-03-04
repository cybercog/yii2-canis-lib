<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\security\identity\providers;

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
