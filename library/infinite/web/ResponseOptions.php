<?php
namespace infinite\web;

use Yii;

use infinite\base\ObjectTrait;


class ResponseOptions extends \infinite\base\Object {
	use ObjectTrait;
	
	public $justStatus = false;
	public $error;
	public $success;

	public $refresh = false;
	public $redirect = false;

	public $ajaxDialog;
	public $ajaxDialogSettings;

}
?>