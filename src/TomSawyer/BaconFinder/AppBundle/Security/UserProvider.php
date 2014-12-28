<?php

namespace TomSawyer\BaconFinder\AppBundle\Security;

use TomSawyer\BaconFinder\AppBundle\TomSawyerBaconFinderEvents;
use TomSawyer\BaconFinder\AppBundle\Event\SocialConnectEvent;
use TomSawyer\BaconFinder\AppBundle\Model\User;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use TomSawyer\BaconFinder\AppBundle\Manager\UserManager;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserProvider implements OAuthAwareUserProviderInterface, UserProviderInterface
{
    protected $userManager;

    protected $context;

    protected $eventDispatcher;

    public function __construct(UserManager $userManager, TokenStorageInterface $context, EventDispatcherInterface $eventDispatcher)
    {
        $this->userManager = $userManager;
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Authenticate a user by Oauth Service Owner
     *
     * @param UserResponseInterface $response
     * @return bool|mixed|null|User
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        if (null !== $this->context->getToken()) {

            return $this->joinAccount($response, $this->context->getToken()->getUser());
        }
        $resourceOwner = strtolower($response->getResourceOwner()->getName());
        $loadMethod = 'load'.ucfirst($resourceOwner).'User';

        return $this->$loadMethod($response);
    }

    /**
     * @inherit
     */
    public function loadUserByUsername($username)
    {
        return null;
    }

    /**
     * @inherit
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof UserInterface || is_subclass_of($user, $this->userManager->getUserClass())) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $user;
    }

    /**
     * @inherit
     */
    public function supportsClass($class)
    {

        return $class === 'BaconFinder\AppBundle\Model\User';
    }

    private function joinAccount(UserResponseInterface $response, UserInterface $user)
    {
        if ($user->getResourceOwner() === $response->getResourceOwner()->getName()) {

            return $user;
        }

        $refreshedUser = $this->userManager->addSocialProfile($user, $response);
        $this->dispatchSocialConnect($user, true, $response);

        return $refreshedUser;
    }

    /**
     * Loads or create a user logged in with Facebook Login
     *
     * @param UserResponseInterface $response
     * @return null|User
     */
    private function loadFacebookUser(UserResponseInterface $response)
    {
        $user = $this->userManager->findByFacebookId($response->getUsername());
        if (null === $user) {
            $user = $this->userManager->createFacebookUserFromOAuthResponse($response);
        }
        $user->getFacebookProfile()->setToken($response->getAccessToken());
        $this->dispatchSocialConnect($user, false, $response);

        return $user;
    }

    /**
     * Loads or create a user logged in with Twitter login
     *
     * @param UserResponseInterface $response
     * @return null|User
     */
    private function loadTwitterUser(UserResponseInterface $response)
    {
        $user = $this->userManager->findByTwitterId($response->getUsername());
        if (null === $user) {
            $user = $this->userManager->createTwitterUserFromOAuthResponse($response);
        }
        $user->getTwitterProfile()->setToken($response->getAccessToken());
        $this->dispatchSocialConnect($user, false, $response);

        return $user;
    }

    private function dispatchSocialConnect(UserInterface $user, $joined = false, UserResponseInterface $response = null)
    {
        $event = new SocialConnectEvent($user, $joined, $response);
        $this->eventDispatcher->dispatch(TomSawyerBaconFinderEvents::SOCIAL_CONNECT, $event);
    }

}