<?php
namespace infinite\action;

class WebAction extends Action
{
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
