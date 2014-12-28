<?php

namespace TomSawyer\BaconFinder\AppBundle\Importer;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use TomSawyer\BaconFinder\AppBundle\Manager\UserManager;

class SocialImporter
{
    protected $fbImporter;

    protected $twImporter;

    protected $userManager;

    public function __construct(
        FacebookImporter $fbImporter,
        TwitterFriendsImporter $twImporter,
        UserManager $userManager)
    {
        $this->fbImporter = $fbImporter;
        $this->twImporter = $twImporter;
        $this->userManager = $userManager;
    }

    public function import(UserInterface $user, UserResponseInterface $response)
    {
        switch(strtolower($response->getResourceOwner()->getName())) {
            case 'facebook':
                $this->fbImporter->importFriends($response);
                $user->getFacebookProfile()->setLastImportTime(new \DateTime("NOW"));
                $this->userManager->updateUser($user);
                break;
            case 'twitter':
                $this->twImporter->importFriends($response);
                $user->getTwitterProfile()->setLastImportTime(new \DateTime("NOW"));
                $this->userManager->updateUser($user);
                break;
        }

        return $user;
    }



}