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

        return $user;
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
        $friends = [];
        $res = $response->getGraphObject();
        foreach ($res->getPropertyAsArray('data') as $friend) {
            $friends[] = [
                'name' => $friend->getProperty('name'),
                'facebookId' => $friend->getProperty('id')
            ];
        }

        return $friends;
    }

    public function getProfilePicture($token)
    {
        $session = new FacebookSession($token);
        $request = new FacebookRequest(
            $session,
            'GET',
            '/me/picture',
            array(
                'redirect' => false
            )
        );
        $response = $request->execute();
        $res = $response->getGraphObject();
        $url = $res->getProperty('url');

        return $url;
    }
}