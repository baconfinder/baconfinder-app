<?php

namespace TomSawyer\BaconFinder\AppBundle\Security;

use TomSawyer\BaconFinder\AppBundle\Model\User;
use TomSawyer\BaconFinder\AppBundle\Repository\UserRepository;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use TomSawyer\BaconFinder\AppBundle\Manager\UserManager;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Psr\Log\LoggerInterface;

class UserProvider implements OAuthAwareUserProviderInterface, UserProviderInterface
{
    protected $userManager;

    protected $userRepository;

    protected $logger;

    protected $context;

    protected $facebookClient;

    protected $twitterClient;

    public function __construct(
        UserManager $userManager,
        UserRepository $userRepository,
        TokenStorageInterface $context,
        $logger,
        $facebookClient,
        $twitterClient)
    {
        $this->userManager = $userManager;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->context = $context;
        $this->facebookClient = $facebookClient;
        $this->twitterClient = $twitterClient;
    }

    /**
     * Authenticate a user by Oauth Service Owner
     * If the User Token already exist, the other owner infos will be joined
     *
     * @param UserResponseInterface $response
     * @return bool|mixed|null|User
     */
    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $userContext = $this->context->getToken();
        if (null !== $userContext) {
            if ($response->getResourceOwner()->getName() === $userContext->getUser()->getResourceOwner()) {
                return $userContext->getUser();
            }
            switch ($userContext->getUser()->getResourceOwner()) {
                case 'twitter':
                    $join = $this->getFbUserFromResponse($response);
                    $this->userRepository->joinAccount('twitter', $userContext->getUser()->getTwitterid(), $join);
                    $userContext->getUser()->setFacebookId($response->getResponse()['id']);
                    break;
                case 'facebook':
                    $join = $this->getTwitterUserFromResponse($response);
                    $this->userRepository->joinAccount('facebook', $userContext->getUser()->getEmail(), $join);
                    $userContext->getUser()->setTwitterId($response->getResponse()['id']);
                    break;
            }

            return $userContext->getUser();
        }
        $resourceOwner = strtolower($response->getResourceOwner()->getName());

        switch($resourceOwner) {
            case 'facebook':
                $user = $this->loadFacebookUser($response);
                break;
            case 'twitter':
                $user = $this->loadTwitterUser($response);
                break;
            default:
                return false;
        }

        return $user;
    }

    /**
     * Loads or create a user logged in with Facebook Login
     *
     * @param UserResponseInterface $response
     * @return null|User
     */
    private function loadFacebookUser(UserResponseInterface $response)
    {
        $user = $this->userRepository->getFacebookUserByEmail($response->getEmail(), $response->getAccessToken());

        if (null == $response->getEmail()) {
            throw new \InvalidArgumentException('The Facebook E-mail is null');
        }

        if (null === $user) {
            $user = $this->getFbUserFromResponse($response);
            $user->setResourceOwner('facebook');
            $this->userRepository->createUser($user);
        }
        $user->setFacebookToken($response->getAccessToken());

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
        $user = $this->userRepository->getTwitterUserById($response->getUsername(), $response->getAccessToken());

        if (null === $user) {
            $user = $this->getTwitterUserFromResponse($response);
            $user->setResourceOwner('twitter');
            $this->userRepository->createUser($user);
        }
        $user->setTwitterToken($response->getAccessToken());

        return $user;
    }

    /**
     * @inherit
     */
    public function loadUserByUsername($username)
    {
        return null;
    }

    /**
     * Load user from twitterId
     *
     * @param $id
     * @return null|User
     */
    private function loadTwitterUserById($id)
    {
        return $this->userRepository->getTwitterUserById($id);
    }

    /**
     * Load user from Facebook email
     *
     * @param $email
     * @return null|User
     */
    private function loadFacebookUserByEmail($email)
    {
        return $this->userRepository->getFacebookUserByEmail($email);
    }

    /**
     * @inherit
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        switch($user->getResourceOwner()) {
            case 'facebook':
                return $this->loadFacebookUserByEmail($user->getEmail());
            case 'twitter':
                return $this->loadTwitterUserById($user->getTwitterId());
        }
    }

    /**
     * @inherit
     */
    public function supportsClass($class)
    {
        return $class === 'BaconFinder\AppBundle\Model\User';
    }

    /**
     * Create a User instance from Facebook OAuth Response
     *
     * @param UserResponseInterface $response
     * @return User
     */
    private function getFbUserFromResponse(UserResponseInterface $response)
    {
        $user = $this->userManager->createFacebookUser(
            $response->getEmail(),
            $response->getResponse()['id'],
            $response->getResponse()['first_name'],
            $response->getResponse()['last_name'],
            $response->getAccessToken()
        );

        return $user;
    }

    /**
     * Create a User instance from Twitter OAuth Response
     *
     * @param UserResponseInterface $response
     * @return User
     */
    private function getTwitterUserFromResponse(UserResponseInterface $response)
    {
        $user = $this->userManager->createTwitterUser(
            $response->getResponse()['id'],
            $response->getResponse()['screen_name'],
            $response->getResponse()['name'],
            $response->getAccessToken()
        );

        return $user;
    }

}