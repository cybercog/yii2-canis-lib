<?php
namespace infinite\action;

interface InteractiveActionInterface
{
    public function hasInteractions();
    public function handleInteractions($sleep = 30);
    public function createInteraction($label, $options, $callback, $handleNow = true);
    public function getInteractionsPackage();
}
?>
