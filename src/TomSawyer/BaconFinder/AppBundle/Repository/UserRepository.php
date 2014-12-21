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
        $q = 'MATCH (user:ActiveUser {email: {email}})
        WITH user
        MATCH (user)-[:FACEBOOK_PROFILE]->(profile)
        ';
        if (null !== $token) {
            $q .= 'SET profile.token = {token}
            ';
            $p['token'] = $token;
        }
        $q .= 'RETURN user, profile';
        $p['email'] = $email;

        $result = $this->client->sendCypherQuery($q, $p)->getResult();
        $fbUser = $result->get('user');

        if (null === $fbUser) {
            return null;
        }

        $profile = $result->get('profile');
        $token = $profile->hasProperty('token') ? $profile->getProperty('token') : null;

        $user = $this->userManager->createFacebookUser(
            $fbUser->getProperty('email'),
            $profile->getProperty('facebookId'),
            $profile->getProperty('firstname'),
            $profile->getProperty('lastname'),
            $token
        );

        return $user;
    }

    public function getTwitterUser($id, $token)
    {
        $q = 'MATCH (twitter:TwitterProfile {id:{id}})<-[:TWITTER_PROFILE]-(user:User)
        OPTIONAL MATCH (facebook:FacebookProfile)<-[:FACEBOOK_PROFILE]-(user)
        RETURN user, twitter, facebook';
        $p = ['id' => $id];
        $result = $this->client->sendCypherQuery($q, $p)->getResult();
        if (null === $result->get('user')) {
            return null;
        }
    }

    public function createUser(User $user)
    {
        $resourceOwner = strtolower($user->getResourceOwner());

        switch($resourceOwner) {
            case 'facebook':
                return $this->createFacebookUser($user);
            default:
                return false;
        }
    }

    private function createFacebookUser(User $user)
    {
        $q = 'MERGE (user:User {email: {email}})
        SET user :ActiveUser
        MERGE (profile:FacebookProfile {facebookId: {fbId}})
        SET profile.firstname = {firstname}
        SET profile.lastname = {lastname}
        SET profile.token = {token}
        MERGE (user)-[:FACEBOOK_PROFILE]->(profile)';

        $p = [
            'email' => $user->getEmail(),
            'fbId' => $user->getFacebookProfile()->getFacebookId(),
            'firstname' => $user->getFacebookProfile()->getFirstName(),
            'lastname' => $user->getFacebookProfile()->getLastName(),
            'token' => $user->getFacebookProfile()->getToken()
        ];
        $this->client->sendCypherQuery($q, $p);

        return $user;
    }
}