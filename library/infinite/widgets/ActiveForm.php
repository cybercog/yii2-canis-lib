<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\widgets;

/**
 * ActiveForm [@doctodo write class description for ActiveForm]
 *
 * @author Jacob Morrison <email@ofjacob.com>
**/
class ActiveForm extends \yii\widgets\ActiveForm
{
    public $fieldConfig = ['class' => 'infinite\\widgets\\ActiveField'];

    public static function begin($config = [], $echo = true)
    {
        ob_start();
        ob_implicit_flush(false);
        $return = parent::begin($config);
        $result = ob_get_clean();
        if (!$echo) {
            return [$return, $result];
        }
        echo $result;

        return $return;
    }

    public static function end($echo = true)
    {
        ob_start();
        ob_implicit_flush(false);
        parent::end();
        $result = ob_get_clean();
        if (!$echo) {
            return $result;
        }
        echo $result;
    }
}
