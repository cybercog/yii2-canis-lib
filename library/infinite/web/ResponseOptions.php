<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web;

use infinite\base\ObjectTrait;

/**
 * ResponseOptions [@doctodo write class description for ResponseOptions]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class ResponseOptions extends \infinite\base\Object
{

    public $justStatus = false;
    public $error;
    public $success;

    public $refresh = false;
    public $redirect = false;

    public $ajaxDialog;
    public $ajaxDialogSettings;

}
