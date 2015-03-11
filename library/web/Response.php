<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\web;

use teal\base\exceptions\Exception;
use teal\base\ObjectTrait;
use Yii;
use yii\helpers\Url;

/**
 * Response [[@doctodo class_description:teal\web\Response]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Response extends \yii\web\Response
{
    use ObjectTrait;

    /**
     * @var [[@doctodo var_type:controller]] [[@doctodo var_description:controller]]
     */
    public $controller;
    /**
     * @var [[@doctodo var_type:action]] [[@doctodo var_description:action]]
     */
    public $action;
    /**
     * @var [[@doctodo var_type:view]] [[@doctodo var_description:view]]
     */
    public $view = false;

    /**
     * @var [[@doctodo var_type:task]] [[@doctodo var_description:task]]
     */
    public $task = 'fill';

    /**
     * @var [[@doctodo var_type:clientTask]] [[@doctodo var_description:clientTask]]
     */
    public $clientTask;
    /**
     * @var [[@doctodo var_type:staticTasks]] [[@doctodo var_description:staticTasks]]
     */
    public $staticTasks = ['status', 'trigger'];
    /**
     * @var [[@doctodo var_type:taskOptions]] [[@doctodo var_description:taskOptions]]
     */
    public $taskOptions = [];
    /**
     * @var [[@doctodo var_type:baseInstructions]] [[@doctodo var_description:baseInstructions]]
     */
    public $baseInstructions = [];

    /**
     * @var [[@doctodo var_type:taskSet]] [[@doctodo var_description:taskSet]]
     */
    public $taskSet = false;
    /**
     * @var [[@doctodo var_type:labels]] [[@doctodo var_description:labels]]
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
     * @var [[@doctodo var_type:trigger]] [[@doctodo var_description:trigger]]
     */
    public $trigger = false;

    /**
     * @var [[@doctodo var_type:forceInstructions]] [[@doctodo var_description:forceInstructions]]
     */
    public $forceInstructions = false;
    /**
     * @var [[@doctodo var_type:forceFlash]] [[@doctodo var_description:forceFlash]]
     */
    public $forceFlash = false;
    /**
     * @var [[@doctodo var_type:disableInstructions]] [[@doctodo var_description:disableInstructions]]
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
     *
     * @return [[@doctodo return_type:getIsInstructable]] [[@doctodo return_description:getIsInstructable]]
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
     * [[@doctodo method_description:generateInstructions]].
     *
     * @throws Exception [[@doctodo exception_description:Exception]]
     * @return [[@doctodo return_type:generateInstructions]] [[@doctodo return_description:generateInstructions]]
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
     * [[@doctodo method_description:handleFill]].
     *
     * @param [[@doctodo param_type:i]] $i [[@doctodo param_description:i]]
     *
     * @return [[@doctodo return_type:handleFill]] [[@doctodo return_description:handleFill]]
     */
    protected function handleFill(&$i)
    {
        return true;
    }

    /**
     * [[@doctodo method_description:handleClient]].
     *
     * @param [[@doctodo param_type:i]] $i [[@doctodo param_description:i]]
     *
     * @return [[@doctodo return_type:handleClient]] [[@doctodo return_description:handleClient]]
     */
    protected function handleClient(&$i)
    {
        $this->task = $this->clientTask;

        return true;
    }

    /**
     * [[@doctodo method_description:handleStatus]].
     *
     * @param [[@doctodo param_type:i]] $i [[@doctodo param_description:i]]
     *
     * @return [[@doctodo return_type:handleStatus]] [[@doctodo return_description:handleStatus]]
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
     * [[@doctodo method_description:handleTrigger]].
     *
     * @param [[@doctodo param_type:i]] $i [[@doctodo param_description:i]]
     *
     * @return [[@doctodo return_type:handleTrigger]] [[@doctodo return_description:handleTrigger]]
     */
    protected function handleTrigger(&$i)
    {
        if (!empty($this->trigger)) {
            $i['trigger'] = $this->trigger;
        }

        return true;
    }

    /**
     * [[@doctodo method_description:handleDialog]].
     *
     * @param [[@doctodo param_type:i]] $i [[@doctodo param_description:i]]
     *
     * @return [[@doctodo return_type:handleDialog]] [[@doctodo return_description:handleDialog]]
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

    /**
     * [[@doctodo method_description:handleMessage]].
     *
     * @param [[@doctodo param_type:i]] $i [[@doctodo param_description:i]]
     *
     * @return [[@doctodo return_type:handleMessage]] [[@doctodo return_description:handleMessage]]
     */
    protected function handleMessage(&$i)
    {
        return true;
    }

    /**
     * [[@doctodo method_description:handleFlashStatus]].
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
     * [[@doctodo method_description:renderContent]].
     *
     * @param boolean $layout [[@doctodo param_description:layout]] [optional]
     *
     * @return [[@doctodo return_type:renderContent]] [[@doctodo return_description:renderContent]]
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
     * [[@doctodo method_description:beforeSend]].
     *
     * @param [[@doctodo param_type:event]] $event [[@doctodo param_description:event]] [optional]
     *
     * @return [[@doctodo return_type:beforeSend]] [[@doctodo return_description:beforeSend]]
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
     *
     * @return [[@doctodo return_type:getRoute]] [[@doctodo return_description:getRoute]]
     */
    public function getRoute()
    {
        if (is_null($this->action)) {
            return;
        }

        return $this->action->getUniqueId();
    }
}
