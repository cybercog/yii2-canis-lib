<?php
/**
 * @link http://canis.io/
 *
 * @copyright Copyright (c) 2015 Canis
 * @license http://canis.io/license/
 */

namespace canis\web;

/**
 * ResponseOptions [[@doctodo class_description:canis\web\ResponseOptions]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ResponseOptions extends \canis\base\Object
{
    /**
     * @var [[@doctodo var_type:justStatus]] [[@doctodo var_description:justStatus]]
     */
    public $justStatus = false;
    /**
     * @var [[@doctodo var_type:error]] [[@doctodo var_description:error]]
     */
    public $error;
    /**
     * @var [[@doctodo var_type:success]] [[@doctodo var_description:success]]
     */
    public $success;

    /**
     * @var [[@doctodo var_type:refresh]] [[@doctodo var_description:refresh]]
     */
    public $refresh = false;
    /**
     * @var [[@doctodo var_type:redirect]] [[@doctodo var_description:redirect]]
     */
    public $redirect = false;

    /**
     * @var [[@doctodo var_type:ajaxDialog]] [[@doctodo var_description:ajaxDialog]]
     */
    public $ajaxDialog;
    /**
     * @var [[@doctodo var_type:ajaxDialogSettings]] [[@doctodo var_description:ajaxDialogSettings]]
     */
    public $ajaxDialogSettings;
}
