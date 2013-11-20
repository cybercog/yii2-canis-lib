<?php
namespace infinite\web;

use Yii;

class Response extends \yii\web\Response
{
	use \infinite\base\ObjectTrait;

	public $controller;
	public $view = false;

	public $justStatus = false;
	public $error;
	public $success;

	public $refresh = false;
	public $redirect = false;

	public $forceInstructions = false;
	public $disableInstructions = false;

	public $ajaxDialog;
	public $ajaxDialogSettings;

	public function init()
	{
		parent::init();
		$this->on(static::EVENT_BEFORE_SEND, [$this, 'beforeSend']);
	}

	public function getIsInstructable()
	{
		$isAjax = (isset(Yii::$app->request->isAjax) && Yii::$app->request->isAjax);
		if (isset($_GET['_instruct'])) {
			if (!empty($_GET['_instruct'])) {
				$this->forceInstructions = true;
				$this->disableInstructions = false;
			} else {
				$this->forceInstructions = false;
				$this->disableInstructions = true;
			}
		}
		return (!empty($this->data) || $this->forceInstructions || $isAjax) && !$this->disableInstructions;
	}

	protected function generateInstructions()
	{
		$d = [];
		if ($this->redirect) {
			$d['redirect'] = $this->redirect;
		} elseif ($this->refresh) {
			$d['refresh'] = true;
		} elseif (!$this->justStatus) {
			$d['content'] = $this->renderContent(false);
		}
		return $d;
	}

	protected function renderContent($layout = true)
	{
		if (isset($this->controller) && $this->view) {
			if ($layout) {
				return $this->controller->render($this->view);
			} else {
				return $this->controller->renderPartial($this->view);
			}
		}
		return null;
	}

	public function beforeSend($event)
	{
		if (isset($this->controller)) {
			if ($this->isInstructable) {
				$this->format = static::FORMAT_JSON;
				$this->data = $this->generateInstructions();
			} elseif ($this->redirect) {
				$this->redirect($this->redirect);
			} elseif ($this->refresh) {
				$this->refresh();
			} else {
				if (is_null($this->content)) {
					$this->content = $this->renderContent(true);
				}
			}
		}
	}
}
?>