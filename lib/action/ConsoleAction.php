<?php
namespace canis\action;

use canis\helpers\Console;

/**
 * ConsoleAction [[@doctodo class_description:canis\action\ConsoleAction]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class ConsoleAction extends Action
{
    /**
     * @inheritdoc
     */
    public function handleInteractions($sleep = 30)
    {
        foreach ($this->_interactions as $id => $interaction) {
            $response = false;
            switch ($interaction->inputType) {
                case 'select':
                    Console::output("Please select one: ");
                    $options = $interaction->options['options'];
                    foreach ($options as $key => $value) {
                        Console::output("\t$key - $value");
                    }
                    $response = Console::select($interaction->label, $options);
                    while (empty($response) || !isset($options[$response])) {
                        $response = Console::select($interaction->label, $options);
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
