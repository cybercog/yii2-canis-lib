<?php
namespace infinite\action;

use Yii;
use infinite\helpers\Console;

class ConsoleAction extends Action
{
    public function handleInteractions($sleep = 30)
    {
        foreach ($this->_interactions as $id => $interaction) {
            $response = false;
            switch ($interaction->inputType) {
                case 'select':
                    $options = $interaction->options['options'];
                    $response = Console::prompt($interaction->label, $options);
                    while (empty($response)) {
                        $response = Console::prompt($interaction->label, $options);
                    }
                break;
                default:
                    $response = Console::input($interaction->label);
                    while (empty($response)) {
                        $response = Console::input($interaction->label);
                    }
                break;
            }
            $interaction->resolve($response);
            if ($interaction->resolved) {
                unset($this->_interactions[$id]);
            }
        }
        return true;
    }
}
?>
