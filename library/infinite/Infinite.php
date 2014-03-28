<?php
/**
 * library/Infinite.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */
defined('INFINITE_ROLE_LEVEL_OWNER') || define('INFINITE_ROLE_LEVEL_OWNER', 500); // owner levels: 401-500
defined('INFINITE_ROLE_LEVEL_MANAGER') || define('INFINITE_ROLE_LEVEL_MANAGER', 400); // manager levels: 301-400
defined('INFINITE_ROLE_LEVEL_EDITOR') || define('INFINITE_ROLE_LEVEL_EDITOR', 300); // editor levels: 201-300
defined('INFINITE_ROLE_LEVEL_COMMENTER') || define('INFINITE_ROLE_LEVEL_COMMENTER', 200); // commenter levels: 101-200; doesn't exist in system
defined('INFINITE_ROLE_LEVEL_VIEWER') || define('INFINITE_ROLE_LEVEL_VIEWER', 100); // viewer levels: 1-100

function d($variable, $settings = []) {
    $default = ['die' => false, 'skipSteps' => 1];
    (new \ICD($variable, array_merge($default, $settings)))->output();
}

function b($backtrace) {
    echo '<pre>';
    foreach ($backtrace as $b) {
        if (!isset($b['file'])) { continue; }
        echo "{$b['file']}:{$b['line']} {$b['function']}\n";
    }
    echo '</pre>';
}

class Infinite implements \yii\base\BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap(\yii\base\Application $app)
    {
        Yii::setAlias('@infinite', __DIR__);
        Yii::$app->registerMigrationAlias('@infinite/db/migrations');
    }
}
?>