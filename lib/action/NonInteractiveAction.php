<?php
namespace canis\action;

/**
 * NonInteractiveAction [[@doctodo class_description:canis\action\NonInteractiveAction]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class NonInteractiveAction extends Action
{
    /**
     * @inheritdoc
     */
    public function handleInteractions($sleep = 30)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function createInteraction($label, $options, $callback, $handleNow = true)
    {
        return false;
    }
}
