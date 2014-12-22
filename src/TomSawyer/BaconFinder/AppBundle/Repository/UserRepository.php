<?php

namespace TomSawyer\BaconFinder\AppBundle\Repository;

use Neoxygen\NeoClient\Client;
use TomSawyer\BaconFinder\AppBundle\Model\User,
    TomSawyer\BaconFinder\AppBundle\Manager\UserManager;

class UserRepository
{
    protected $client;

    protected $userManager;

    protected $logger;

    public function __construct(Client $client, UserManager $userManager)
    {
        $this->client = $client;
        $this->userManager = $userManager;
    }

    public function getFacebookUserByEmail($email, $token = null)
    {
        $q = 'MATCH (user:ActiveUser {email: {email}}) ';
        if (null !== $token) {
            $q .= 'SET user.facebook_token = {token}
            ';
            $p['token'] = $token;
        }
        $q .= 'RETURN user';
        $p['email'] = $email;

        $result = $this->client->sendCypherQuery($q, $p)->getResult();
        $user = $result->get('user');

        if (null === $user) {
            return null;
        }
        $token = $user->hasProperty('facebook_token') ? $user->getProperty('facebook_token') : null;

        $fbUser = $this->userManager->createFacebookUser(
            $user->getProperty('email'),
            $user->getProperty('facebookId'),
            $user->getProperty('firstname'),
            $user->getProperty('lastname'),
            $token
        );
        $fbUser->setTwitterId($user->hasProperty('twitterId'));

        return $fbUser;
    }

    public function getTwitterUserById($id, $token = null)
    {
        $q = 'MATCH (user:User {twitterId: {id}}) RETURN user';
        $p = ['id' => (int) $id];
        $result = $this->client->sendCypherQuery($q, $p)->getResult();
        if (null === $result->get('user')) {
            return null;
        }
        $tUser = $result->get('user');
        $user = new User();
        $user->setResourceOwner('twitter');
        $user->setTwitterToken($token);
        $user->setTwitterId($tUser->getProperty('twitterId'));
        $user->setTwitterScreenName($tUser->getProperty('screenName'));
        $user->setTwitterName($tUser->getProperty('name'));
        $user->setFacebookId($tUser->hasProperty('facebookId'));

        return $user;
    }

    public function createUser(User $user)
    {
        $resourceOwner = strtolower($user->getResourceOwner());

        switch($resourceOwner) {
            case 'facebook':
                return $this->createFacebookUser($user);
            case 'twitter':
                return $this->createTwitterUser($user);
            default:
                return false;
        }
    }

    public function joinAccount($owner, $username, User $user)
    {
        switch ($owner) {
            case 'facebook':
                $q = 'MATCH (user:User {email:{username}})
                SET user.twitterId = {twitterId}
                SET user.twitterName = {twitterName}
                SET user.twitterScreenName = {screenName}
                SET user.twitterToken = {token}';
                $p = [
                    'username' => $username,
                    'twitterId' => (int) $user->getTwitterId(),
                    'twitterName' => $user->getTwitterName(),
                    'screenName' => $user->getTwitterScreenName(),
                    'token' => $user->getTwitterToken()
                ];
                $this->client->sendCypherQuery($q, $p);
                break;

            case 'twitter':
                $q = 'MATCH (user:User {twitterId: {id}})
                SET user.facebookId = {fbId}
                SET user.firstname = {firstname}
                SET user.lastname = {lastname}
                SET user.facebookToken = {token}
                RETURN user';
                $p = [
                    'id' => (int) $username,
                    'fbId' => (int) $user->getFacebookId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'token' => $user->getFacebookToken()
                ];
                $this->client->sendCypherQuery($q, $p);
                break;
        }
    }

    private function createTwitterUser(User $user)
    {
        $q = 'MERGE (user:User {twitterId: {id}})
        SET user :ActiveUser
        SET user.name = {name}
        SET user.screenName = {screenName}
        SET user.twitterToken = {token}
        RETURN user';
        $p = [
            'id' => (int) $user->getTwitterId(),
            'screenName' => $user->getTwitterScreenName(),
            'token' => $user->getTwitterToken(),
            'name'=> $user->getTwitterName()
        ];
        $this->client->sendCypherQuery($q, $p);

        return $user;
    }

    private function createFacebookUser(User $user)
    {
        $q = 'MERGE (user:User {email: {email}})
        SET user :ActiveUser
        SET user.facebookId = {fbId}
        SET user.firstname = {firstname}
        SET user.lastname = {lastname}
        SET user.facebook_token = {token}
        RETURN user';

        $p = [
            'email' => $user->getEmail(),
            'fbId' => (int) $user->getFacebookId(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'token' => $user->getFacebookToken()
        ];
        $this->client->sendCypherQuery($q, $p);

        return $user;
    }
}