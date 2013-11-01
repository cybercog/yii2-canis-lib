<?php
/**
 * library/setup/Setup.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\setup;
use Exception;
use Task;
use Migrator;
use Yii;

class Setup extends \infinite\base\Object
{
    public static $_instance;
    public static $_migrator;
    public static $_app;
    public $basePath;
    public $name = 'Application';
    public $pageTitle = 'Setup';
    public $params = array();
    public $neededInformation = array();

    public static function createSetupApplication($config = array())
    {
        defined('YII_DEBUG') OR define('YII_DEBUG', true);
        defined('INFINITE_APP_SETUP') OR define('INFINITE_APP_SETUP', true);
        if (is_null(self::$_instance)) {
            $className = __CLASS__;
            self::$_instance = new $className($config);
        }
        return self::$_instance;
    }

    public function __construct($config = array())
    {
        foreach ($config as $k => $v) {
            $this->{$k} = $v;
        }
        if (is_null($this->basePath)) {
            $this->basePath = dirname(dirname(__FILE__));
        }
    }

    public function beforeRun()
    {
        return true;
    }

    public function afterRun()
    {
           return true;
    }

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
        foreach ($tasks as $task) {
            if (!$this->beforeRun()) {
                $this->render('message');
                return false;
            }

            // try {
                $tasksLeft--;
                if ($task->test()) {
                    continue;
                }
                if (!empty($task->verification) AND !$this->getConfirmed($task->id)) {
                    // show confirm link
                    $this->params['question'] = $task->verification;
                    $this->params['task'] = $task;
                    $this->render('confirm');
                } elseif(!empty($task->fields) AND (empty($_POST[$task->id]) OR !$this->getConfirmed($task->id) OR !$task->loadInput($_POST[$task->id]))) {
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
                        if (!empty($task->verification) OR !empty($task->fields)) {
                            $this->refresh("Successfully completed the task <em>{$task->title}</em>");
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

    public function refresh($message = null)
    {
        //echo '<pre>';var_dump($_SERVER);exit;
        $url = $_SERVER['SCRIPT_NAME'] . '?message='.$message;
        header('Location: '.$url);
        exit(0);
    }


    public function getIsSetup()
    {
        // if (!$this->isEnvironmented) { return false; }
        // if ($this->version > $this->instanceVersion) { return false; }
        // if ($this->migrator->check()) { return false; }
        // if (!$this->app()) { return false; }

        $steps = array();

        return true;
    }

    public function getSetupTasks()
    {
        $self = $this;
        $tasks = array();
        $tasksPath = $this->applicationPath.DIRECTORY_SEPARATOR .'setup'.DIRECTORY_SEPARATOR.'tasks';
        if (!is_dir($tasksPath)) {
            return $tasks;
        }
        $handle = opendir($tasksPath);
        while(($file = readdir($handle))!==false) {
            if($file === '.' || $file === '..') {
                continue;
            }
            $path = $tasksPath . DIRECTORY_SEPARATOR . $file;
            if(preg_match('/^Task_(\d{6}\_.*?)\.php$/',$file,$matches) AND is_file($path)) {
                $className = '\app\setup\tasks\Task_'.$matches[1];
                if (!include_once($path)) { continue; }
                $task = new $className($this);
                $tasks[$task->id] = $task;
            }
        }
        closedir($handle);
        ksort($tasks);
        return $tasks;
    }

    public function getIsAvailable()
    {
        if (!$this->isEnvironmented) { return false; }
        if ($this->version > $this->instanceVersion) { return false; }
        return true;
    }

    public function getConfirmLink($task)
    {
        return $_SERVER['REQUEST_URI'] . '?task='.$task.'&confirm='. $this->getConfirmSalt($task);
    }

    public function getConfirmSalt($task = null)
    {
        return md5(date("Y-m-d") . __FILE__ .':'. $task);
    }

    public function getConfirmed($task)
    {
        $confirm = null;
        if (isset($_GET['confirm'])) {
            $confirm = $_GET['confirm'];
        }
        if (isset($_POST['confirm'])) {
            $confirm = $_POST['confirm'];
        }
        if (isset($confirm) AND $confirm === $this->getConfirmSalt($task)) {
            return true;
        }
        return false;
    }

    public function getVersion()
    {
        return trim(file_get_contents($this->basePath . DIRECTORY_SEPARATOR .'VERSION'));
    }

    public function getInstanceVersion()
    {
        if ($this->isEnvironmented) {
            return INFINITE_APP_INSTANCE_VERSION;
        }
        return false;
    }

    public function app()
    {
        if ($this->isEnvironmented) {
            if (is_null(self::$_app)) {
                $configPath = $this->environmentPath . DIRECTORY_SEPARATOR . 'console.php';
                if (!file_exists($configPath)) {
                    throw new Exception("Couldn't find environment config {$configPath}!");
                }
                $_SERVER['argv'] = array();
                $config = include($configPath);
                self::$_app = new \infinite\console\Application($config);
            }
            return self::$_app;
        }
        return false;
    }

    public function getIsEnvironmented()
    {
        if (file_exists($this->environmentFilePath)) {
            include_once($this->environmentFilePath);
        }
        if (isset($_GET['reset'])) {
        //	return false; // don't want to let this just sit here. could be a big security risk.
        }
        return defined('INFINITE_APP_INSTANCE_VERSION');
    }

    public function getEnvironmentPath()
    {
        if ($this->isEnvironmented) {
            return INFINITE_APP_ENVIRONMENT_PATH;
        }
        return false;
    }

    public function getApplicationPath()
    {
        $path = $this->basePath . DIRECTORY_SEPARATOR . 'app';
        if (!is_dir($path)) {
            throw new Exception("Application path does not exist: {$path}");
        }
        return $path;
    }

    public function getConfigPath()
    {
        $path = $this->applicationPath . DIRECTORY_SEPARATOR . 'config';
        if (!is_dir($path)) {
            throw new Exception("Config path does not exist: {$path}");
        }
        return $path;
    }

    public function getEnvironmentFilePath()
    {
        $path = $this->configPath . DIRECTORY_SEPARATOR . 'env.php';
        return $path;
    }

    public function getEnvironmentTemplateFilePath()
    {
        $path = $this->environmentFilePath . '.sample';
        return $path;
    }

    public function getEnvironmentsPath()
    {
        $path = $this->configPath . DIRECTORY_SEPARATOR . 'environments';
        if (!is_dir($path)) {
            throw new Exception("Base environment path does not exist: {$path}");
        }
        return $path;
    }

    public function getBaseEnvironmentPath()
    {
        $path = $this->environmentsPath . DIRECTORY_SEPARATOR . 'common';
        if (!is_dir($path)) {
            throw new Exception("Base environment path does not exist: {$path}");
        }
        return $path;
    }

    public function getEnvironmentSetupsPath()
    {
        $path = $this->environmentsPath . DIRECTORY_SEPARATOR . 'setups';
        if (!is_dir($path)) {
            throw new Exception("Environment setups path does not exist: {$path}");
        }
        return $path;
    }
    public function getEnvironmentTemplatesPath()
    {
        $path = $this->environmentsPath . DIRECTORY_SEPARATOR . 'templates';
        if (!is_dir($path)) {
            throw new Exception("Environment templates path does not exist: {$path}");
        }
        return $path;
    }

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
        include($viewFile);
        $content = ob_get_clean();
        ob_start();
        include($layoutFile);
        ob_end_flush();
        exit(0);
    }
}
