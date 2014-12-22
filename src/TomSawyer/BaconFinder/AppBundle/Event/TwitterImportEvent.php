<?php

namespace TomSawyer\BaconFinder\AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class TwitterImportEvent extends Event
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}