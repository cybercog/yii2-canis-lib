<?php
namespace teal\security\identity\providers;

interface CreatorInterface
{
    public function getIdentityProvider();
    public function setIdentityProvider($idp);
    public function attemptCreate($username, $password);
}
