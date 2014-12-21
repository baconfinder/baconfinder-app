<?php

namespace TomSawyer\BaconFinder\AppBundle\Manager;

use TomSawyer\BaconFinder\AppBundle\Model\User,
    TomSawyer\BaconFinder\AppBundle\Model\FacebookProfile;

class UserManager
{
    public function createFacebookUser($email, $facebookId, $firstname, $lastname, $token)
    {
        $user = new User();
        $user->setEmail($email);
        $user->setResourceOwner('facebook');

        $profile = new FacebookProfile();
        $profile->setFirstName($firstname);
        $profile->setLastName($lastname);
        $profile->setFacebookId($facebookId);
        $profile->setToken($token);

        $user->setFacebookProfile($profile);

        return $user;
    }

    public function createTwitterUser($handle, $twitterId, $firstname, $lastname, $token)
    {
        $user = new User();
    }
}