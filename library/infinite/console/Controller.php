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
**/
class Controller extends \yii\console\Controller
{
    public function hr()
    {
        list($width, $height) = Console::getScreenSize();
        $this->out(str_repeat("=", $width));
    }

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
