<?php
/**
 * library/Canis.php.
 *
 * @author Jacob Morrison <jacob@canis.io>
 */
defined('TEAL_ROLE_LEVEL_OWNER') || define('TEAL_ROLE_LEVEL_OWNER', 600); // owner levels: 501-600
defined('TEAL_ROLE_LEVEL_MANAGER') || define('TEAL_ROLE_LEVEL_MANAGER', 500); // manager levels: 401-500
defined('TEAL_ROLE_LEVEL_EDITOR') || define('TEAL_ROLE_LEVEL_EDITOR', 400); // editor levels: 301-400
defined('TEAL_ROLE_LEVEL_COMMENTER') || define('TEAL_ROLE_LEVEL_COMMENTER', 300); // commenter levels: 201-300; doesn't exist in system
defined('TEAL_ROLE_LEVEL_VIEWER') || define('TEAL_ROLE_LEVEL_VIEWER', 200); // viewer levels: 101-200
defined('TEAL_ROLE_LEVEL_BROWSER') || define('TEAL_ROLE_LEVEL_BROWSER', 100); // viewer levels: 1-100

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
 * Canis [[@doctodo class_description:Canis]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Canis implements \yii\base\BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        Yii::setAlias('@canis', __DIR__);
        Yii::$app->registerMigrationAlias('@canis/db/migrations');
        if ($app instanceof \yii\console\Application) {
            $app->controllerMap['cron'] = [
                'class' => 'canis\console\controllers\CronController',
            ];
            $app->controllerMap['daemon'] = [
                'class' => 'canis\console\controllers\DaemonController',
            ];
        }
    }
}
