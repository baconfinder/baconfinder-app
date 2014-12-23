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
        $fbUser->setUuid($user->getProperty('uuid'));

        return $fbUser;
    }

    public function getTwitterUserById($id, $token = null)
    {
        $q = 'MATCH (user:ActiveUser {twitterId: {id}}) RETURN user';
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
        $user->setTwitterScreenName($tUser->getProperty('twitterScreenName'));
        $user->setTwitterName($tUser->getProperty('twitterName'));
        $user->setFacebookId($tUser->hasProperty('facebookId'));
        $user->setUuid($tUser->getProperty('uuid'));

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

    public function joinAccount($owner, $uuid, User $user)
    {
        switch ($owner) {
            case 'facebook':
                $q = 'MATCH (user:ActiveUser {uuid: {uuid}})
                SET user.twitterId = {twitterId}
                SET user.twitterName = {twitterName}
                SET user.twitterScreenName = {screenName}
                SET user.twitterToken = {token}';
                $p = [
                    'twitterId' => (int) $user->getTwitterId(),
                    'twitterName' => $user->getTwitterName(),
                    'screenName' => $user->getTwitterScreenName(),
                    'token' => $user->getTwitterToken(),
                    'uuid' => $uuid
                ];
                $this->client->sendCypherQuery($q, $p);
                break;

            case 'twitter':
                $q = 'MATCH (user:ActiveUser {uuid: {uuid}})
                SET user.facebookId = {fbId}
                SET user.firstname = {firstname}
                SET user.lastname = {lastname}
                SET user.facebookToken = {token}
                SET user.email = {email}
                RETURN user';
                $p = [
                    'fbId' => (int) $user->getFacebookId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'token' => $user->getFacebookToken(),
                    'email' => $user->getEmail(),
                    'uuid' => $uuid
                ];
                $this->client->sendCypherQuery($q, $p);
                break;
        }
    }

    public function getUserConnectionsCount(User $user)
    {
        $q = 'MATCH (n:ActiveUser {uuid: {uuid}})
        OPTIONAL MATCH (n)-[:CONNECT]->(o)
        RETURN count(o) as connections';
        $p = [
            'uuid' => $user->getUuid()
        ];

        $result = $this->client->sendCypherQuery($q, $p)->getResult();

        return $result->get('connections');
    }

    public function searchActiveUser($term)
    {
        $q = 'MATCH (n:User) WHERE n.twitterName =~ {term}
        OR n.twitterScreenName =~ {term}
        OR n.firstname =~ {term}
        OR n.lastname =~ {term}
        OR n.name =~ {term}
        RETURN n';
        $p = ['term' => '(?i)'.$term.'.*'];

        $result = $this->client->sendCypherQuery($q, $p)->getResult();

        return $result->get('n', null, true);
    }

    public function getUserInfo($uuid)
    {
        $q = 'MATCH (n:User {uuid: {uuid}}) RETURN n';
        $p = ['uuid' => $uuid];

        $result = $this->client->sendCypherQuery($q, $p)->getResult();

        return $result->get('n');
    }

    private function createTwitterUser(User $user)
    {
        $q = 'MERGE (user:User {twitterId: {id}})
        ON CREATE
        SET user :ActiveUser, user.uuid = {uuid}, user.twitterName = {name},
        user.twitterScreenName = {screenName}, user.twitterToken = {token}
        ON MATCH
        SET user.twitterName = {name}, user.twitterScreenName = {screenName}, user.twitterToken = {token}
        RETURN user';
        $p = [
            'id' => (int) $user->getTwitterId(),
            'screenName' => $user->getTwitterScreenName(),
            'token' => $user->getTwitterToken(),
            'name'=> $user->getTwitterName(),
            'uuid' => $user->getUuid()
        ];
        $this->client->sendCypherQuery($q, $p);

        return $user;
    }

    private function createFacebookUser(User $user)
    {
        $q = 'MERGE (user:User {facebookId: {fbId}})
        ON CREATE SET user.uuid = {uuid}
        SET user :ActiveUser
        SET user.firstname = {firstname}
        SET user.lastname = {lastname}
        SET user.facebook_token = {token}
        SET user.email = {email}
        RETURN user';

        $p = [
            'email' => $user->getEmail(),
            'fbId' => (int) $user->getFacebookId(),
            'firstname' => $user->getFirstName(),
            'lastname' => $user->getLastName(),
            'token' => $user->getFacebookToken(),
            'uuid' => $user->getUuid()
        ];
        $this->client->sendCypherQuery($q, $p);

        return $user;
    }
}