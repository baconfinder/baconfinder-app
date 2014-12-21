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
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setFacebookId($facebookId);
        $user->setFacebookToken($token);

        return $user;
    }

    public function createTwitterUser($twitterId, $handle, $name, $token)
    {
        $user = new User();
        $user->setTwitterScreenName($handle);
        $user->setTwitterName($name);
        $user->setTwitterId($twitterId);
        $user->setTwitterToken($token);
        $user->setResourceOwner('twitter');

        return $user;
    }
}