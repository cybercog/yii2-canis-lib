<?php
/**
 * library/Infinite.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */

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