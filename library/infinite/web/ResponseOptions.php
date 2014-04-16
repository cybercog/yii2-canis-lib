<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web;

use infinite\base\ObjectTrait;

class ResponseOptions extends \infinite\base\Object
{
    use ObjectTrait;

    public $justStatus = false;
    public $error;
    public $success;

    public $refresh = false;
    public $redirect = false;

    public $ajaxDialog;
    public $ajaxDialogSettings;

}
