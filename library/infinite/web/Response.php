<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web;

use infinite\base\exceptions\Exception;
use infinite\base\ObjectTrait;
use Yii;
use yii\helpers\Url;

/**
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Response extends \yii\web\Response
{
    use ObjectTrait;

    /**
     */
    public $controller;
    /**
     */
    public $action;
    /**
     */
    public $view = false;

    /**
     */
    public $task = 'fill';

    public $clientTask;
    /**
     */
    public $staticTasks = ['status', 'trigger'];
    /**
     */
    public $taskOptions = [];
    /**
     */
    public $baseInstructions = [];

    public $taskSet = false;
    /**
     */
    public $labels = [
        'submit' => 'Save',
        'cancel' => 'Cancel',
        'confirm_yes' => 'Yes',
        'confirm_delete' => 'Submit',
        'confirm_cancel' => 'Cancel',
        'confirm_no' => 'No',
        'close' => 'Close',
    ];

    /**
     */
    public $error;
    /**
     */
    public $success;

    /**
     */
    public $refresh = false;
    /**
     */
    public $redirect = false;

    /**
     */
    public $trigger = false;

    /**
     */
    public $forceInstructions = false;
    public $forceFlash = false;
    /**
     */
    public $disableInstructions = false;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        //$this->on(static::EVENT_BEFORE_SEND, [$this, 'beforeSend']);
    }

    /**
     * Get is instructable.
     */
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

        return (is_array($this->data) || $this->forceInstructions || $isAjax) && !$this->disableInstructions;
    }

    /**
     *
     */
    protected function generateInstructions()
    {
        if (is_array($this->data)) {
            return $this->data;
        }
        $i = $this->baseInstructions;

        $keepProcessing = true;

        // high priority tasks
        if ($this->redirect) {
            $keepProcessing = false;
            $i['redirect'] = Url::to($this->redirect);
            $i['task'] = 'redirect';
        } elseif ($this->refresh) {
            $keepProcessing = false;
            $i['task'] = 'refresh';
        }

        if (!$keepProcessing) {
            // @todo set status flashes
            $this->handleFlashStatus();

            return $i;
        } elseif ($this->forceFlash) {
            $this->handleFlashStatus();
        }
        if (!in_array($this->getStatusCode(), [302])) {
            $delayedInstructions = json_decode(Yii::$app->session->getFlash('delayed-instructions', json_encode([])), true);
            //var_dump($delayedInstructions);exit;
            if (is_array($delayedInstructions)) {
                $i = array_merge($i, $delayedInstructions);
            } else {
                var_dump($delayedInstructions);
            }
        }

        if (!empty($this->trigger)) {
            $i['trigger'] = $this->trigger;
        }

        if (!in_array($this->task, $this->staticTasks)) {
            $i['content'] = $this->renderContent(false);

            $method = 'handle' . ucfirst($this->task);
            if (method_exists($this, $method) && $this->$method($i)) {
                $i['task'] = $this->task;
                $i['taskOptions'] = $this->taskOptions;
            } else {
                throw new Exception("Invalid response task {$this->task}!");
            }
        }

        foreach ($this->staticTasks as $task) {
            $method = 'handle' . ucfirst($task);
            if (method_exists($this, $method) && !$this->$method($i)) {
                throw new Exception("Invalid response task {$task}!");
            }
        }

        if ($this->taskSet) {
            $i['taskSet'] = $this->taskSet;
        }

        return $i;
    }

    /**
     *
     */
    protected function handleFill(&$i)
    {
        return true;
    }

    /**
     *
     */
    protected function handleClient(&$i)
    {
        $this->task = $this->clientTask;

        return true;
    }

    /**
     *
     */
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

    /**
     *
     */
    protected function handleTrigger(&$i)
    {
        if (!empty($this->trigger)) {
            $i['trigger'] = $this->trigger;
        }

        return true;
    }

    /**
     *
     */
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
                $this->taskOptions['buttons'][$this->labels['confirm_delete']] = ['role' => 'submit', 'state' => 'danger', 'class' => 'delete-button-label'];
                $this->taskOptions['buttons'][$this->labels['confirm_cancel']] = ['role' => 'close'];
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

    protected function handleMessage(&$i)
    {
        return true;
    }

    /**
     */
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

    /**
     *
     */
    protected function renderContent($layout = true)
    {
        if (isset($this->content)) {
            return $this->content;
        }
        if (is_null(Yii::$app->controller) && !is_null($this->controller)) {
            Yii::$app->controller = $this->controller;
        }
        if (is_null(Yii::$app->controller->action) && !is_null($this->action)) {
            Yii::$app->controller->action = $this->action;
        }

        if (isset($this->controller) && $this->view) {
            if ($layout) {
                return $this->controller->render($this->view);
            } else {
                return $this->controller->renderPartial($this->view);
            }
        }

        return;
    }
    /**
     * @inheritdoc
     */
    public function send()
    {
        if (!$this->isSent && $this->statusCode !== 500) {
            $this->beforeSend();
        }
        parent::send();
    }

    /**
     *
     */
    public function beforeSend($event = null)
    {
        if (isset($this->stream)) {
            return;
        }
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

    /**
     * @inheritdoc
     */
    public function redirect($url, $statusCode = 302, $checkAjax = true)
    {
        if (is_array($url) && isset($url[0])) {
            // ensure the route is absolute
            $url[0] = '/' . ltrim($url[0], '/');
        }
        $url = Url::to($url);
        if (strpos($url, '/') === 0 && strpos($url, '//') !== 0) {
            $url = Yii::$app->getRequest()->getHostInfo() . $url;
        }

        $this->getHeaders()->set('Location', $url);
        $this->setStatusCode($statusCode);

        return $this;
    }

    /**
     * Get route.
     */
    public function getRoute()
    {
        if (is_null($this->action)) {
            return;
        }

        return $this->action->getUniqueId();
    }
}
