<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web;

/**
 * ResponseOptions [[@doctodo class_description:teal\web\ResponseOptions]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ResponseOptions extends \teal\base\Object
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
