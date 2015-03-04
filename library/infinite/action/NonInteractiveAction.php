<?php
namespace infinite\action;

class NonInteractiveAction extends Action
{
    public function handleInteractions($sleep = 30)
    {
        return false;
    }

    public function createInteraction($label, $options, $callback, $handleNow = true)
    {
        return false;
    }
}
