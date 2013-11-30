<?php
/**
 * library/Infinite.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


use Yii;

class Infinite extends \yii\base\Extension
{
    /**
     * @inheritdoc
     */
    public static function init()
    {
    	throw new \Exception("boom");
    	parent::init();
        Yii::setAlias('@infinite', __DIR__);
        Yii::$app->registerMigrationAlias('@infinite/db/migrations');
    }
}