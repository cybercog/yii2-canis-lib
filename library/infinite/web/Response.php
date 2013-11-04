<?php
namespace infinite\web;

use Yii;

class Response extends \infinite\base\Object {
	public $error = false;
	public $success = false;
	public $redirect = false;
	public $refresh = false;
	public $view = false;
	public $dialog = false;
	public $justStatus = false;
	public $suppressNotifications = false;
	public $dialogSettings = array();
	public $renderer = 'render';

	public $end = true;
	public $ajaxPackage = array();
	protected $_controller;



	/**
	 *
	 *
	 * @param unknown $view       (optional)
	 * @param unknown $settings   (optional)
	 * @param unknown $controller (optional)
	 */
	public function __construct($view = false, $settings = array(), $controller = null) {
		$this->view = $view;
		$this->_controller = $controller;
		foreach ($settings as $k => $v) {
			$this->{$k} = $v;
		}
	}


	/**
	 *
	 *
	 * @return unknown
	 */
	public function getController() {
		if (is_null($this->_controller)) {
			$this->_controller = Yii::$app->getController();
		}
		return $this->_controller;
	}


	/**
	 *
	 *
	 * @return unknown
	 */
	public function handle() {
		if (Yii::$app->request->isAjaxRequest) {
			return $this->handleAjax();
		} else {
			return $this->handleRegular();
		}
	}


	/**
	 *
	 */
	public function handlePartial() {
		$this->end = false;
		$this->renderer = 'renderPartial';
		$this->handleRegular();
	}


	/**
	 *
	 */
	public function handleAjax() {
		$package = $this->ajaxPackage;
		if ($this->suppressNotifications) {
			$package['suppressNotifications'] = true;
		}
		if ($this->redirect) {
			if ($this->error) {
				Yii::$app->user->setFlash('error', $this->error);
			}elseif ($this->success) {
				Yii::$app->user->setFlash('success', $this->success);
			}
			if (is_array($this->redirect)) {
				$this->redirect = CHtml::normalizeUrl($this->redirect);
			}
			$package['redirect'] = $this->redirect;
		} else {
			if ($this->error) {
				$package['error'] = $this->error;
			} elseif ($this->success) {
				$package['success'] = $this->success;
			}
			if ($this->refresh) {
				$package['refresh'] = $this->refresh;
			}elseif ($this->justStatus) {
				$package['justStatus'] = true;
			} else {
				if ($this->dialog and $this->view) {
					$package['dialog'] = array_merge($this->defaultDialogSettings(), $this->dialogSettings);
					$package['dialog']['content'] = $this->controller->renderPartial($this->view, $this->controller->params, true);
				} elseif ($this->view) {
					$package['content'] = $this->controller->renderPartial($this->view, $this->controller->params, true);
				}
			}
		}
		$this->controller->json($package, true);
		if ($this->end) {
			Yii::$app->end();
		}
	}


	/**
	 *
	 */
	public function handleRegular() {
		if ($this->error) {
			Yii::$app->user->setFlash('error', $this->error);
		}elseif ($this->success) {
			Yii::$app->user->setFlash('success', $this->success);
		}
		if ($this->redirect) {
			$this->controller->redirect($this->redirect);
		}
		if ($this->refresh) {
			$this->controller->refresh();
		}
		$this->controller->{$this->renderer}($this->view, $this->controller->params);
		if ($this->end) {
			Yii::$app->end();
		}
	}


	/**
	 *
	 *
	 * @return unknown
	 */
	public function defaultDialogSettings() {
		return array(
			'title' => 'Action',
			//'width' => '80%',
			//'position' => 'top',
			//'modal' => true,
			// 'saveButtonLabel' => 'Save',
			// 'closeButtonLabel' => 'Cancel',
		);
	}
}
?>