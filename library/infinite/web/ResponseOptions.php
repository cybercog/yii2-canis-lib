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

    /**
     * @var __var_justStatus_type__ __var_justStatus_description__
     */
    public $justStatus = false;
    /**
     * @var __var_error_type__ __var_error_description__
     */
    public $error;
    /**
     * @var __var_success_type__ __var_success_description__
     */
    public $success;

    /**
     * @var __var_refresh_type__ __var_refresh_description__
     */
    public $refresh = false;
    /**
     * @var __var_redirect_type__ __var_redirect_description__
     */
    public $redirect = false;

    public $ajaxDialog;
    /**
     * @var __var_ajaxDialogSettings_type__ __var_ajaxDialogSettings_description__
     */
    /**
     * @var __var_ajaxDialog_type__ __var_ajaxDialog_description__
     */
    public $ajaxDialogSettings;

}
