<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\web;

use Yii;

use yii\helpers\Url;
use infinite\base\exceptions\Exception;
use infinite\base\ObjectTrait;

/**
 * Response [@doctodo write class description for Response]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class Response extends \yii\web\Response
{
    use ObjectTrait;

    /**
     * @var __var_controller_type__ __var_controller_description__
     */
    public $controller;
    /**
     * @var __var_action_type__ __var_action_description__
     */
    public $action;
    /**
     * @var __var_view_type__ __var_view_description__
     */
    public $view = false;

    /**
     * @var __var_task_type__ __var_task_description__
     */
    public $task = 'fill';
    /**
     * @var __var_staticTasks_type__ __var_staticTasks_description__
     */
    public $staticTasks = ['status', 'trigger'];
    /**
     * @var __var_taskOptions_type__ __var_taskOptions_description__
     */
    public $taskOptions = [];
    /**
     * @var __var_baseInstructions_type__ __var_baseInstructions_description__
     */
    public $baseInstructions = [];
    /**
     * @var __var_labels_type__ __var_labels_description__
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

    /**
     * @var __var_trigger_type__ __var_trigger_description__
     */
    public $trigger = false;

    /**
     * @var __var_forceInstructions_type__ __var_forceInstructions_description__
     */
    public $forceInstructions = false;
    /**
     * @var __var_disableInstructions_type__ __var_disableInstructions_description__
     */
    public $disableInstructions = false;

    /**
    * @inheritdoc
    **/
    public function init()
    {
        parent::init();
        //$this->on(static::EVENT_BEFORE_SEND, [$this, 'beforeSend']);
    }

    /**
     * Get is instructable
     * @return __return_getIsInstructable_type__ __return_getIsInstructable_description__
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
     * __method_generateInstructions_description__
     * @return __return_generateInstructions_type__ __return_generateInstructions_description__
     * @throws Exception __exception_Exception_description__
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
        }

        if (!empty($this->trigger)) {
            $i['trigger'] = $this->trigger;
        }

        if (!in_array($this->task, $this->staticTasks)) {
            $i['content'] = $this->renderContent(false);

            $method = 'handle'.ucfirst($this->task);
            if (method_exists($this, $method) && $this->$method($i)) {
                $i['task'] = $this->task;
                $i['taskOptions'] = $this->taskOptions;
            } else {
                throw new Exception("Invalid response task {$this->task}!");
            }
        }

        foreach ($this->staticTasks as $task) {
            $method = 'handle'.ucfirst($task);
            if (method_exists($this, $method) && !$this->$method($i)) {
                throw new Exception("Invalid response task {$task}!");
            }
        }

        return $i;
    }

    /**
     * __method_handleFill_description__
     * @param __param_i_type__ $i __param_i_description__
     * @return __return_handleFill_type__ __return_handleFill_description__
     */
    protected function handleFill(&$i)
    {
        return true;
    }

    /**
     * __method_handleStatus_description__
     * @param __param_i_type__ $i __param_i_description__
     * @return __return_handleStatus_type__ __return_handleStatus_description__
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
     * __method_handleTrigger_description__
     * @param __param_i_type__ $i __param_i_description__
     * @return __return_handleTrigger_type__ __return_handleTrigger_description__
     */
    protected function handleTrigger(&$i)
    {
        if (!empty($this->trigger)) {
            $i['trigger'] = $this->trigger;
        }

        return true;
    }

    /**
     * __method_handleDialog_description__
     * @param __param_i_type__ $i __param_i_description__
     * @return __return_handleDialog_type__ __return_handleDialog_description__
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
     * __method_handleFlashStatus_description__
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
     * __method_renderContent_description__
     * @param boolean $layout __param_layout_description__ [optional]
     * @return __return_renderContent_type__ __return_renderContent_description__
     */
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
    /**
    * @inheritdoc
    **/
    public function send()
    {
        if (!$this->isSent && $this->statusCode !== 500) {
            $this->beforeSend();
        }
        parent::send();
    }

    /**
     * __method_beforeSend_description__
     * @param __param_event_type__ $event __param_event_description__ [optional]
     */
    public function beforeSend($event = null)
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

    /**
    * @inheritdoc
    **/
    public function redirect($url, $statusCode = 302)
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
     * Get route
     * @return __return_getRoute_type__ __return_getRoute_description__
     */
    public function getRoute()
    {
        if (is_null($this->action)) { return; }

        return $this->action->getUniqueId();
    }
}
