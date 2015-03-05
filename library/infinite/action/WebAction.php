<?php
namespace infinite\action;

/**
 * WebAction [[@doctodo class_description:infinite\action\WebAction]].
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class WebAction extends Action
{
    /**
     * @inheritdoc
     */
    public function handleInteractions($sleep = 30)
    {
        $this->pauseAction();
        $this->resolveInteractions();
        while ($this->hasInteractions()) {
            sleep($sleep);
            $this->resolveInteractions();
        }
        $this->resumeAction();

        return true;
    }
}
