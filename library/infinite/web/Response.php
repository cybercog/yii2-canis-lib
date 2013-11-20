<?php
namespace infinite\web;

use Yii;

use \infinite\base\exceptions\Exception;

class Response extends \yii\web\Response
{
	use \infinite\base\ObjectTrait;

	public $controller;
	public $view = false;

	public $task = 'fill';
	public $taskOptions = [];
	public $labels = [
		'submit' => 'Save',
		'cancel' => 'Cancel',
		'confirm_yes' => 'Yes',
		'confirm_delete' => 'Delete',
		'confirm_no' => 'No',
		'close' => 'Close',
	];

	public $error;
	public $success;

	public $refresh = false;
	public $redirect = false;

	public $forceInstructions = false;
	public $disableInstructions = false;

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
		$i = [];
		$keepProcessing = true; 

		// high priority tasks
		if ($this->redirect) {
			$keepProcessing = false; 
			$i['redirect'] = $this->redirect;
		} elseif ($this->refresh) {
			$keepProcessing = false; 
			$i['refresh'] = true;
		}

		if (!$keepProcessing) {
			// @todo set status flashes
			$this->handleFlashStatus();
			return $i;
		}

		if ($this->task !== 'status') {
			$i['content'] = $this->renderContent(false);
		}

		$method = 'handle'.ucfirst($this->task);
		if (method_exists($this, $method) && $this->$method($i)) {
			$i['task'] = $this->task;
			$i['taskOptions'] = $this->taskOptions;
		} else {
			throw new Exception("Invalid response task {$this->task}!");
		}

		return $i;
	}

	protected function handleFill(&$i)
	{
		return true;
	}

	protected function handleStatus(&$i)
	{
		if (!empty($this->error)) {
			$i['error'] = true;
			$i['message'] = $this->error;
		} elseif (!empty($this->success)) {
			$i['success'] = true;
			$i['message'] = $this->success;
		}
		return true;
	}

	protected function handleDialog(&$i)
	{
		if (!isset($this->taskOptions['isForm'])) {
			$this->taskOptions['isForm'] = strstr($this->content, '<form') === false;
		}

		if (!isset($this->taskOptions['isConfirmation'])) {
			$this->taskOptions['isConfirmation'] = false;
		}

		if (!isset($this->taskOptions['isConfirmDeletion'])) {
			$this->taskOptions['isConfirmDeletion'] = false;
		}

		if (!isset($this->taskOptions['buttons'])) {
			$this->taskOptions['buttons'] = [];
			if ($this->taskOptions['isConfirmDeletion']) {
				$this->taskOptions['buttons'][$this->labels['confirm_delete']] = ['role' => 'submit', 'state' => 'warning'];
				$this->taskOptions['buttons'][$this->labels['confirm_no']] = ['role' => 'close'];
			} elseif ($this->taskOptions['isConfirmation']) {
				$this->taskOptions['buttons'][$this->labels['confirm_yes']] = ['role' => 'submit'];
				$this->taskOptions['buttons'][$this->labels['confirm_no']] = ['role' => 'close'];
			} elseif ($this->taskOptions['isForm']) {
				$this->taskOptions['buttons'][$this->labels['submit']] = ['role' => 'submit'];
				$this->taskOptions['buttons'][$this->labels['cancel']] = ['role' => 'close'];
			} else {
				$this->taskOptions['buttons'][$this->labels['close']] = ['role' => 'close'];
			}

		}
		return true;
	}

	protected function handleFlashStatus()
	{
		if (isset(Yii::$app->session)) {
			foreach (['error', 'success'] as $status) {
				if (!empty($this->$status)) {
					Yii::$app->session->setFlash($status, $this->$status);
				}
			}
		}
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
			} else {
				$this->handleFlashStatus();
				if ($this->redirect) {
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
}
?>