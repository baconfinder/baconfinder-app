<?php

namespace TomSawyer\BaconFinder\AppBundle\Manager;

use TomSawyer\BaconFinder\AppBundle\Model\User;
use GraphAware\UuidBundle\Service\UuidService;

class UserManager
{
    protected $uuidService;

    public function __construct(UuidService $uuidService)
    {
        $this->uuidService = $uuidService;
    }

    public function createFacebookUser($email, $facebookId, $firstname, $lastname, $token)
    {
        $user = new User();
        $user->setUuid($this->uuidService->getUuid());
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
        $user->setUuid($this->uuidService->getUuid());
        $user->setTwitterScreenName($handle);
        $user->setTwitterName($name);
        $user->setTwitterId($twitterId);
        $user->setTwitterToken($token);
        $user->setResourceOwner('twitter');

        return $user;
    }
}