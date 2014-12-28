<?php

namespace TomSawyer\BaconFinder\AppBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use TomSawyer\BaconFinder\AppBundle\Model\User;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;

class SocialConnectEvent extends Event
{
    protected $user;

    protected $joined;

    protected $response;

    public function __construct(User $user, $joined = false, UserResponseInterface $response = null)
    {
        $this->user = $user;
        $this->joined = $joined;
        $this->response = $response;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function isJoinedAccount()
    {
        return $this->joined;
    }

    public function getResponse()
    {
        return $this->response;
    }

}