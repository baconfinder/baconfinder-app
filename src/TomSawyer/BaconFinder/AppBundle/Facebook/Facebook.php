<?php

namespace TomSawyer\BaconFinder\AppBundle\Facebook;

use Facebook\FacebookSession,
    Facebook\FacebookRequest,
    Facebook\GraphUser;

class Facebook
{
    protected $session;

    public function __construct($appId, $appSecret)
    {
        $this->session = FacebookSession::setDefaultApplication($appId, $appSecret);
    }

    public function getUserProfile($token)
    {
        $session = new FacebookSession($token);
        $request = new FacebookRequest(
            $session,
            'GET',
            '/me'
        );
        $user = $request->execute()->getGraphObject(GraphUser::className());
        print_r($user);
    }

    public function getUserFriends($token)
    {
        $session = new FacebookSession($token);
        $request = new FacebookRequest(
            $session,
            'GET',
            '/me/friends'
        );
        $response = $request->execute();

        $friends = $response->getGraphObject();
        print_r($friends);
    }
}