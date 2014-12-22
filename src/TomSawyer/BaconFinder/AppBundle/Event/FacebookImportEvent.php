<?php

namespace TomSawyer\BaconFinder\AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class FacebookImportEvent extends Event
{
    protected $user;

    protected $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getToken()
    {
        return $this->token;
    }
}