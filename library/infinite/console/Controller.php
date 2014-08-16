<?php
/**
 * @link http://www.infinitecascade.com/
 * @copyright Copyright (c) 2014 Infinite Cascade
 * @license http://www.infinitecascade.com/license/
 */

namespace infinite\console;

use Yii;

use yii\helpers\Console;

/**
 * Controller [@doctodo write class description for Controller]
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class Controller extends \yii\console\Controller
{
    public $started = false;
    public function runAction($id, $params = [])
    {
        $this->on(self::EVENT_BEFORE_ACTION, function($event) {
            $event->sender->started = true;
        });
        return parent::runAction($id, $params);
    }
    /**
     * __method_hr_description__
     */
    public function hr()
    {
        //list($width, $height) = Console::getScreenSize();
        $width = 100;
        $this->out(str_repeat("=", $width));
    }

    /**
     * __method_out_description__
     * @param __param_string_type__ $string __param_string_description__
     * @return __return_out_type__ __return_out_description__
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
