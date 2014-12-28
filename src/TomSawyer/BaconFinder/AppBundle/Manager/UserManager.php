<?php

namespace TomSawyer\BaconFinder\AppBundle\Manager;

use Neoxygen\NeoClient\Exception\HttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use TomSawyer\BaconFinder\AppBundle\Model\User,
    TomSawyer\BaconFinder\AppBundle\Model\FacebookProfile,
    TomSawyer\BaconFinder\AppBundle\Model\TwitterProfile;
use TomSawyer\BaconFinder\AppBundle\Repository\UserRepository;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;

class UserManager
{
    protected $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function findByFacebookId($id)
    {
        $user = $this->repository->getFacebookUserById((int) $id);
        if (null !== $user) {
            $user->setResourceOwner('facebook');
        }

        return $user;
    }

    public function findByTwitterId($id)
    {
        $user = $this->repository->getTwitterUserById((int) $id);
        if (null !== $user) {
            $user->setResourceOwner('twitter');
        }

        return $user;
    }

    public function addSocialProfile(UserInterface $user, UserResponseInterface $response)
    {
        $resourceProfile = ucfirst(strtolower($response->getResourceOwner()->getName())).'Profile';
        $getter = 'get'.$resourceProfile;
        if ($user->$getter() !== null) {
            throw new \InvalidArgumentException(sprintf('The User has already a "%s".', $resourceProfile));
        }
        $createMethod = 'createOAuth'.$resourceProfile;
        $profile = $this->$createMethod($response);
        $setter = 'set'.$resourceProfile;
        $user->$setter($profile);

        return $this->updateUser($user);

    }

    public function updateUser(UserInterface $user)
    {
        $this->repository->update($user);

        return $user;
    }

    public function createUser()
    {
        $user = new User();

        return $user;
    }

    public function createFacebookUserFromOAuthResponse(UserResponseInterface $response)
    {
        $user = $this->createUser();
        $user->setResourceOwner('facebook');
        $profile = $this->createOAuthFacebookProfile($response);
        $user->setFacebookProfile($profile);

        try {
            return $this->repository->createUser($user);
        } catch (HttpException $e) {
            throw new \RuntimeException(sprintf('Unable to create Facebook user with ID "%d"', $response->getUsername()));
        }
    }

    public function createTwitterUserFromOAuthResponse(UserResponseInterface $response)
    {
        $user = $this->createUser();
        $user->setResourceOwner('twitter');
        $profile = $this->createOAuthTwitterProfile($response);
        $user->setTwitterProfile($profile);

        try {
            return $this->repository->createUser($user);
        } catch (HttpException $e) {
            throw new \RuntimeException(sprintf('Unable to create Twitter user with ID "%d"', $response->getUsername()));
        }
    }

    public function getUserClass()
    {
        return 'TomSawyer\BaconFinder\AppBundle\Model\User';
    }

    private function createOAuthFacebookProfile(UserResponseInterface $response)
    {
        $profile = new FacebookProfile($response->getUsername());
        $profile->setEmail($response->getResponse()['email']);
        $profile->setFirstname($response->getResponse()['first_name']);
        $profile->setLastname($response->getResponse()['last_name']);
        $profile->setToken($response->getAccessToken());

        return $profile;
    }

    private function createOAuthTwitterProfile(UserResponseInterface $response)
    {
        $profile = new TwitterProfile($response->getUsername());
        $profile->setScreenName($response->getResponse()['screen_name']);
        $profile->setName($response->getResponse()['name']);
        $profile->setToken($response->getAccessToken());

        return $profile;
    }
}