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

    protected $twitterImporter;

    public function __construct(
        UserManager $userManager,
        UserRepository $userRepository,
        TokenStorageInterface $context,
        $logger,
        $facebookClient,
        $twitterClient,
        $twitterImporter)
    {
        $this->userManager = $userManager;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->context = $context;
        $this->facebookClient = $facebookClient;
        $this->twitterClient = $twitterClient;
        $this->twitterImporter = $twitterImporter;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        $userContext = $this->context->getToken();
        //print_r($response->getResponse());
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

    private function loadFacebookUser(UserResponseInterface $response)
    {
        $user = $this->userRepository->getFacebookUserByEmail($response->getEmail(), $response->getAccessToken());

        if (null == $response->getEmail()) {
            throw new \InvalidArgumentException('The Facebook E-mail is null');
        }

        if (null === $user) {
            $user = $this->userManager->createFacebookUser(
                $response->getEmail(),
                $response->getResponse()['id'],
                $response->getResponse()['first_name'],
                $response->getResponse()['last_name'],
                $response->getAccessToken()
            );
            $this->userRepository->createUser($user);
        }
        $user->getFacebookProfile()->setToken($response->getAccessToken());

        return $user;
    }

    private function loadTwitterUser(UserResponseInterface $response)
    {
        //$user = $this->userRepository->getTwitterUser($response->getUsername(), $response->getAccessToken());
        $this->twitterImporter->importFriendsForUser($response->getUsername());
        exit();

        if (null === $user) {
            $user = $this->userManager->createFacebookUser(
                $response->getEmail(),
                $response->getResponse()['id'],
                $response->getResponse()['first_name'],
                $response->getResponse()['last_name'],
                $response->getAccessToken()
            );
            $this->userRepository->createUser($user);
        }
        $user->getFacebookProfile()->setToken($response->getAccessToken());

        return $user;
    }

    public function loadUserByUsername($username)
    {
        return $this->userRepository->getFacebookUserByEmail($username);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        if ($user->shouldBeReloaded()) {
            $this->logger->debug('Reloading user from the database');
            return $this->loadUserByUsername($user->getEmail());
        } else {
            $this->logger->debug('User should not be reloaded from the database');
            $user->incReloaded();
        }
        return $user;
    }

    public function supportsClass($class)
    {
        return $class === 'BaconFinder\AppBundle\Model\User';
    }

}