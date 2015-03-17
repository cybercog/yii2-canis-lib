<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\security\identity\providers;

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
