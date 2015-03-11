<?php
/**
 * @link http://teal.blue/
 *
 * @copyright Copyright (c) 2015 Teal Software
 * @license http://teal.blue/license/
 */

namespace teal\widgets;

/**
 * ActiveForm [[@doctodo class_description:teal\widgets\ActiveForm]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ActiveForm extends \yii\widgets\ActiveForm
{
    /**
     * @inheritdoc
     */
    public $fieldConfig = ['class' => 'teal\widgets\ActiveField'];

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
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
