<?php
/**
 * library/Infinite.php.
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 */
defined('INFINITE_ROLE_LEVEL_OWNER') || define('INFINITE_ROLE_LEVEL_OWNER', 600); // owner levels: 501-600
defined('INFINITE_ROLE_LEVEL_MANAGER') || define('INFINITE_ROLE_LEVEL_MANAGER', 500); // manager levels: 401-500
defined('INFINITE_ROLE_LEVEL_EDITOR') || define('INFINITE_ROLE_LEVEL_EDITOR', 400); // editor levels: 301-400
defined('INFINITE_ROLE_LEVEL_COMMENTER') || define('INFINITE_ROLE_LEVEL_COMMENTER', 300); // commenter levels: 201-300; doesn't exist in system
defined('INFINITE_ROLE_LEVEL_VIEWER') || define('INFINITE_ROLE_LEVEL_VIEWER', 200); // viewer levels: 101-200
defined('INFINITE_ROLE_LEVEL_BROWSER') || define('INFINITE_ROLE_LEVEL_BROWSER', 100); // viewer levels: 1-100

function d($variable, $settings = [])
{
    $default = ['die' => false, 'skipSteps' => 1];
    (new \ICD($variable, array_merge($default, $settings)))->output();
}

function b($backtrace)
{
    echo '<pre>';
    foreach ($backtrace as $b) {
        if (!isset($b['file'])) {
            continue;
        }
        echo "{$b['file']}:{$b['line']} {$b['function']}\n";
    }
    echo '</pre>';
}

/**
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Infinite implements \yii\base\BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        Yii::setAlias('@infinite', __DIR__);
        Yii::$app->registerMigrationAlias('@infinite/db/migrations');
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap['cron'] = [
                'class' => 'infinite\console\controllers\CronController',
            ];
            $app->controllerMap['daemon'] = [
                'class' => 'infinite\console\controllers\DaemonController',
            ];
        }
    }
}
