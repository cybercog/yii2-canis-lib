<?php
/**
 * @link http://www.infinitecascade.com/
 *
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console;

use Yii;
use yii\helpers\Console;

/**
 * Controller [[@doctodo class_description:infinite\console\Controller]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Controller extends \yii\console\Controller
{
    /**
     * @var [[@doctodo var_type:started]] [[@doctodo var_description:started]]
     */
    public $started = false;
    /**
     * @inheritdoc
     */
    public function runAction($id, $params = [])
    {
        $this->on(self::EVENT_BEFORE_ACTION, function ($event) {
            $event->sender->started = true;
        });

        return parent::runAction($id, $params);
    }
    /**
     * [[@doctodo method_description:hr]].
     */
    public function hr()
    {
        //list($width, $height) = Console::getScreenSize();
        $width = 100;
        $this->out(str_repeat("=", $width));
    }

    /**
     * [[@doctodo method_description:out]].
     *
     * @param [[@doctodo param_type:string]] $string [[@doctodo param_description:string]]
     *
     * @return [[@doctodo return_type:out]] [[@doctodo return_description:out]]
     */
    public function out($string)
    {
        $string = $string . PHP_EOL;
        if ($this->isColorEnabled()) {
            $args = func_get_args();
            array_shift($args);
            $string = Console::ansiFormat($string, $args);
        }

        return Console::stdout($string);
    }
}
