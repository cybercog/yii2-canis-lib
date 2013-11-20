<?php
namespace infinite\web;

use Yii;


class ResponseOptions extends \infinite\base\Object {
	use \infinite\base\ObjectTrait;
	
	public $justStatus = false;
	public $error;
	public $success;

	public $refresh = false;
	public $redirect = false;

	public $ajaxDialog;
	public $ajaxDialogSettings;

}
?>