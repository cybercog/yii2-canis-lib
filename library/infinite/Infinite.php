<?php
/**
 * library/Infinite.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite;

use Yii;

class Extension extends \yii\base\Extension
{
        /**
         * @inheritdoc
         */
        public static function init()
        {
                Yii::setAlias('@infinite', __DIR__);
        }
}