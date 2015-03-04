<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\setup;

use Exception;
use Migrator;
use Task;
use Yii;

defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

/**
 * Setup [@doctodo write class description for Setup].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Setup extends \infinite\base\Object
{
    /**
     * @var __var__instance_type__ __var__instance_description__
     */
    public static $_instance;
    /**
     * @var __var__migrator_type__ __var__migrator_description__
     */
    public static $_migrator;
    /**
     * @var __var__app_type__ __var__app_description__
     */
    public static $_app;
    /**
     * @var __var_basePath_type__ __var_basePath_description__
     */
    public $basePath;
    /**
     * @var __var_applicationPath_type__ __var_applicationPath_description__
     */
    public $applicationPath;
    /**
     * @var __var_name_type__ __var_name_description__
     */
    public $name = 'Application';
    /**
     * @var __var_pageTitle_type__ __var_pageTitle_description__
     */
    public $pageTitle = 'Setup';
    /**
     * @var __var_applicationNamespace_type__ __var_applicationNamespace_description__
     */
    public $applicationNamespace = 'app';
    /**
     * @var __var_params_type__ __var_params_description__
     */
    public $params = [];
    /**
     * @var __var_neededInformation_type__ __var_neededInformation_description__
     */
    public $neededInformation = [];

    /**
     * __method_createSetupApplication_description__.
     *
     * @param array $config __param_config_description__ [optional]
     *
     * @return __return_createSetupApplication_type__ __return_createSetupApplication_description__
     */
    public static function createSetupApplication($config = [])
    {
        defined('YII_DEBUG') or define('YII_DEBUG', true);
        defined('INFINITE_APP_SETUP') or define('INFINITE_APP_SETUP', true);
        if (is_null(self::$_instance)) {
            $className = __CLASS__;
            self::$_instance = new $className($config);
        }

        return self::$_instance;
    }

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        foreach ($config as $k => $v) {
            $this->{$k} = $v;
        }
        if (is_null($this->basePath)) {
            $this->basePath = dirname(dirname(__FILE__));
        }
    }

    /**
     * __method_beforeRun_description__.
     *
     * @return __return_beforeRun_type__ __return_beforeRun_description__
     */
    public function beforeRun()
    {
        return true;
    }

    /**
     * __method_afterRun_description__.
     *
     * @return __return_afterRun_type__ __return_afterRun_description__
     */
    public function afterRun()
    {
        return true;
    }

    /**
     * __method_run_description__.
     *
     * @return __return_run_type__ __return_run_description__
     */
    public function run()
    {
        $self = $this;
        $tasks = $this->setupTasks;
        $currentTask = null;
        $tasksLeft = count($tasks);
        $tasksDone = 0;
        $message = 'set up and is up-to-date';
        if ($this->isEnvironmented) {
            $message = 'updated successfully';
        }
        $skip = [];
        if (isset($_GET['skip'])) {
            $skip = explode(',', $_GET['skip']);
        }
        $newSkip = false;
        foreach ($tasks as $task) {
            if (!$this->beforeRun()) {
                $this->render('message');

                return false;
            }
            if (defined('INFINITE_SETUP_DB_READY') && INFINITE_SETUP_DB_READY) {
                $this->app(); //initialize the app
            }
            if (in_array($task->id, $skip) && $task->skipComplete) {
                $task->skip();
                continue;
            }
            // try {
                $tasksLeft--;
            if ($task->test()) {
                if ($task->skipComplete) {
                    $skip[] = $task->id;
                    $newSkip = true;
                }
                continue;
            }
            if (!empty($task->verification) && !$this->getConfirmed($task->id)) {
                // show confirm link
                    $this->params['question'] = $task->verification;
                $this->params['task'] = $task;
                $this->render('confirm');
            } elseif (!empty($task->fields) && (empty($_POST[$task->id]) || !$this->getConfirmed($task->id) || !$task->loadInput($_POST[$task->id]))) {
                // show form
                    $this->params['fields'] = $task->fields;
                $this->params['task'] = $task;
                $this->render('form');
            } else {
                // do it
                    if (!$task->run()) {
                        $this->params['message'] = "An error occurred while running the task <em>{$task->title}</em>";
                        $this->params['errors'] = $task->errors;
                        $this->params['error'] = true;
                        $this->render('message');
                    } else {
                        $this->afterRun();
                        $tasksDone++;
                        if ($task->skipComplete) {
                            $skip[] = $task->id;
                            $newSkip = true;
                        }
                        if (!empty($task->verification) || !empty($task->fields) || $newSkip) {
                            $this->refresh("Successfully completed the task <em>{$task->title}</em>", $skip);
                            break;
                        }
                    }
            }
            // } catch (Exception $e) {

            //     $message = 'Fatal error: '. $e->getFile() .':'. $e->getLine() .' '. $e->getMessage();
            //     $this->params['message'] = $message;
            //     $this->params['error'] = true;
            //     $this->render('message');
            //     break;
            // }
        }

        $this->params['message'] = "Your application has been {$message}!";
        $this->params['forceContinue'] = true;
        $this->render('message');

        return true;
    }

    /**
     * __method_refresh_description__.
     *
     * @param __param_message_type__ $message __param_message_description__ [optional]
     * @param boolean                $skip    __param_skip_description__ [optional]
     */
    public function refresh($message = null, $skip = false)
    {
        //echo '<pre>';var_dump($_SERVER);exit;
        $url = $_SERVER['SCRIPT_NAME'] . '?message=' . $message;
        if (isset($skip)) {
            $url .= '&skip=' . implode(',', $skip);
        }
        header('Location: ' . $url);
        exit(0);
    }

    /**
     * Get is setup.
     *
     * @return __return_getIsSetup_type__ __return_getIsSetup_description__
     */
    public function getIsSetup()
    {
        // if (!$this->isEnvironmented) { return false; }
        // if ($this->version > $this->instanceVersion) { return false; }
        // if ($this->migrator->check()) { return false; }
        // if (!$this->app()) { return false; }

        $steps = [];

        return true;
    }

    /**
     * Get setup tasks.
     *
     * @return __return_getSetupTasks_type__ __return_getSetupTasks_description__
     */
    public function getSetupTasks()
    {
        $self = $this;
        $tasks = [];
        $tasksPath = $this->applicationPath . DIRECTORY_SEPARATOR . 'setup' . DIRECTORY_SEPARATOR . 'tasks';
        if (!is_dir($tasksPath)) {
            return $tasks;
        }
        $handle = opendir($tasksPath);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $tasksPath . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^Task_(\d{6}\_.*?)\.php$/', $file, $matches) and is_file($path)) {
                $className = $this->applicationNamespace . '\\setup\\tasks\\Task_' . $matches[1];
                if (!include_once($path)) {
                    continue;
                }
                $task = new $className($this);
                $tasks[$task->id] = $task;
            }
        }
        closedir($handle);
        ksort($tasks);

        return $tasks;
    }

    /**
     * Get is available.
     *
     * @return __return_getIsAvailable_type__ __return_getIsAvailable_description__
     */
    public function getIsAvailable()
    {
        if (!$this->isEnvironmented) {
            return false;
        }
        if ($this->version > $this->instanceVersion) {
            return false;
        }

        return true;
    }

    /**
     * __method_markDbReady_description__.
     *
     * @return __return_markDbReady_type__ __return_markDbReady_description__
     */
    public function markDbReady()
    {
        if (!defined('INFINITE_SETUP_DB_READY')) {
            self::$_app = null;
            define('INFINITE_SETUP_DB_READY', true);
        }

        return true;
    }

    /**
     * Get confirm link.
     *
     * @param __param_task_type__ $task __param_task_description__
     *
     * @return __return_getConfirmLink_type__ __return_getConfirmLink_description__
     */
    public function getConfirmLink($task)
    {
        return $_SERVER['REQUEST_URI'] . '?task=' . $task . '&confirm=' . $this->getConfirmSalt($task);
    }

    /**
     * Get confirm salt.
     *
     * @param __param_task_type__ $task __param_task_description__ [optional]
     *
     * @return __return_getConfirmSalt_type__ __return_getConfirmSalt_description__
     */
    public function getConfirmSalt($task = null)
    {
        return md5(date("Y-m-d") . __FILE__ . ':' . $task);
    }

    /**
     * Get confirmed.
     *
     * @param __param_task_type__ $task __param_task_description__
     *
     * @return __return_getConfirmed_type__ __return_getConfirmed_description__
     */
    public function getConfirmed($task)
    {
        $confirm = null;
        if (isset($_GET['confirm'])) {
            $confirm = $_GET['confirm'];
        }
        if (isset($_POST['confirm'])) {
            $confirm = $_POST['confirm'];
        }
        if (isset($confirm) and $confirm === $this->getConfirmSalt($task)) {
            return true;
        }

        return false;
    }

    /**
     * Get version.
     *
     * @return __return_getVersion_type__ __return_getVersion_description__
     */
    public function getVersion()
    {
        return trim(file_get_contents($this->basePath . DIRECTORY_SEPARATOR . 'VERSION'));
    }

    /**
     * Get instance version.
     *
     * @return __return_getInstanceVersion_type__ __return_getInstanceVersion_description__
     */
    public function getInstanceVersion()
    {
        if ($this->isEnvironmented) {
            return INFINITE_APP_INSTANCE_VERSION;
        }

        return false;
    }

    /**
     * __method_app_description__.
     *
     * @throws Exception __exception_Exception_description__
     *
     * @return __return_app_type__ __return_app_description__
     */
    public function app()
    {
        if ($this->isEnvironmented) {
            if (is_null(self::$_app)) {
                $configPath = $this->environmentPath . DIRECTORY_SEPARATOR . 'console.php';
                if (!file_exists($configPath)) {
                    throw new Exception("Couldn't find environment config {$configPath}!");
                }
                $_SERVER['argv'] = [];
                $config = include $configPath;
                self::$_app = Yii::$app = new \infinite\console\Application($config);
                Yii::$app->trigger(\yii\base\Application::EVENT_BEFORE_REQUEST);
            }

            return self::$_app;
        }

        return false;
    }

    /**
     * Get is environmented.
     *
     * @return __return_getIsEnvironmented_type__ __return_getIsEnvironmented_description__
     */
    public function getIsEnvironmented()
    {
        if (file_exists($this->environmentFilePath)) {
            include_once $this->environmentFilePath;
        }
        if (isset($_GET['reset'])) {
            //  return false; // don't want to let this just sit here. could be a big security risk.
        }

        return defined('INFINITE_APP_INSTANCE_VERSION');
    }

    /**
     * Get environment path.
     *
     * @return __return_getEnvironmentPath_type__ __return_getEnvironmentPath_description__
     */
    public function getEnvironmentPath()
    {
        if ($this->isEnvironmented) {
            return INFINITE_APP_ENVIRONMENT_PATH;
        }

        return false;
    }

    /**
     * Get config path.
     *
     * @throws Exception __exception_Exception_description__
     *
     * @return __return_getConfigPath_type__ __return_getConfigPath_description__
     */
    public function getConfigPath()
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . 'config';
        if (!is_dir($path)) {
            throw new Exception("Config path does not exist: {$path}");
        }

        return $path;
    }

    /**
     * Get environment file path.
     *
     * @return __return_getEnvironmentFilePath_type__ __return_getEnvironmentFilePath_description__
     */
    public function getEnvironmentFilePath()
    {
        $path = $this->configPath . DIRECTORY_SEPARATOR . 'env.php';

        return $path;
    }

    /**
     * Get environment template file path.
     *
     * @return __return_getEnvironmentTemplateFilePath_type__ __return_getEnvironmentTemplateFilePath_description__
     */
    public function getEnvironmentTemplateFilePath()
    {
        $path = $this->environmentFilePath . '.sample';

        return $path;
    }

    /**
     * Get library config path.
     *
     * @throws Exception __exception_Exception_description__
     *
     * @return __return_getLibraryConfigPath_type__ __return_getLibraryConfigPath_description__
     */
    public function getLibraryConfigPath()
    {
        $path = INFINITE_APP_PATH . DIRECTORY_SEPARATOR . 'config';
        if (!is_dir($path)) {
            throw new Exception("Library config path does not exist: {$path}");
        }

        return $path;
    }

    /**
     * Get common config path.
     *
     * @throws Exception __exception_Exception_description__
     *
     * @return __return_getCommonConfigPath_type__ __return_getCommonConfigPath_description__
     */
    public function getCommonConfigPath()
    {
        $path = $this->libraryConfigPath . DIRECTORY_SEPARATOR . 'common';
        if (!is_dir($path)) {
            throw new Exception("Base environment path does not exist: {$path}");
        }

        return $path;
    }

    /**
     * Get environment templates path.
     *
     * @throws Exception __exception_Exception_description__
     *
     * @return __return_getEnvironmentTemplatesPath_type__ __return_getEnvironmentTemplatesPath_description__
     */
    public function getEnvironmentTemplatesPath()
    {
        $path = $this->libraryConfigPath . DIRECTORY_SEPARATOR . 'templates';
        if (!is_dir($path)) {
            throw new Exception("Environment templates path does not exist: {$path}");
        }

        return $path;
    }

    /**
     * __method_render_description__.
     *
     * @param __param_view_type__ $view __param_view_description__
     *
     * @throws Exception __exception_Exception_description__
     */
    public function render($view)
    {
        $basePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'views';
        $viewFile = $basePath . DIRECTORY_SEPARATOR . $view . '.php';
        $layoutFile = $basePath . DIRECTORY_SEPARATOR . 'layout.php';
        if (!file_exists($viewFile)) {
            throw new Exception("Invalid setup view file!");
        }
        if (!file_exists($layoutFile)) {
            throw new Exception("Invalid setup layout file!");
        }
        foreach ($this->params as $k => $v) {
            $$k = $v;
        }
        ob_start();
        include $viewFile;
        $content = ob_get_clean();
        ob_start();
        include $layoutFile;
        ob_end_flush();
        exit(0);
    }
}
