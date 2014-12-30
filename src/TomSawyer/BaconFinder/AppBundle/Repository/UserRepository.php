<?php

namespace TomSawyer\BaconFinder\AppBundle\Repository;

use GraphAware\UuidBundle\Service\UuidService;
use Neoxygen\NeoClient\Client;
use TomSawyer\BaconFinder\AppBundle\Model\SocialProfileInterface;
use TomSawyer\BaconFinder\AppBundle\Model\User;
use TomSawyer\BaconFinder\AppBundle\Model\UserMapper;

class UserRepository
{
    protected $client;

    protected $mapper;

    protected $uuid;

    public function __construct(Client $client, UuidService $uuid)
    {
        $this->client = $client;
        $this->mapper = new UserMapper();
        $this->uuid = $uuid;
    }

    public function findUserByUuid($uuid)
    {
        $q = 'MATCH (user:User {uuid: {uuid}})
        OPTIONAL MATCH (user)-[:FACEBOOK_PROFILE]->(fb)
        OPTIONAL MATCH (user)-[:TWITTER_PROFILE]->(tw)
        RETURN user, fb, tw';
        $p = ['uuid' => (int) $uuid];

        $result = $this->client->sendCypherQuery($q, $p)->getResult();
        if (null !== $result->get('user')) {

            return $this->mapper->hydrate($result->get('user'), $result->get('fb'), $result->get('tw'));
        }

        return null;
    }

    public function getFacebookUserById($id)
    {
        $q = 'MATCH (fb:FacebookProfile {id: {id}})
        WITH fb
        MATCH (fb)<-[:FACEBOOK_PROFILE]-(u)
        OPTIONAL MATCH (u)-[:TWITTER_PROFILE]->(tw)
        RETURN u, fb, tw';
        $p = ['id' => $id];

        $result = $this->client->sendCypherQuery($q, $p)->getResult();
        if ($result->get('u') !== null) {
            return $this->mapper->hydrate($result->get('u'), $result->get('fb'), $result->get('tw'));
        }

        return null;
    }

    public function getTwitterUserById($id)
    {
        $q = 'MATCH (tw:TwitterProfile {id: {id}})
        WITH tw
        OPTIONAL MATCH (tw)<-[:TWITTER_PROFILE]-(u)
        OPTIONAL MATCH (u)-[:FACEBOOK_PROFILE]->(fb)
        RETURN u, tw, fb';
        $p = ['id' => (int) $id];

        $result = $this->client->sendCypherQuery($q, $p)->getResult();
        if ($result->get('u') !== null) {
            return $this->mapper->hydrate($result->get('u'), $result->get('fb'), $result->get('tw'));
        }

        return null;
    }

    public function update(User $user)
    {
        $q = 'MATCH (user:User {uuid: {user}.uuid}) ';
        if (null !== $user->getFacebookProfile()) {
            if (null === $user->getFacebookProfile()->getUuid()) {
                $user->getFacebookProfile()->setUuid($this->uuid->getUuid());
            }
            $q .= 'MERGE (fb:FacebookProfile {id: {user}.facebookProfile.id})
            ON CREATE SET fb.first_name = {user}.facebookProfile.first_name,
            fb.last_name = {user}.facebookProfile.last_name,
            fb.email = {user}.facebookProfile.email,
            fb.uuid = {user}.facebookProfile.uuid,
            SET fb.token = {user}.facebookProfile.token
            SET fb.last_import_time = {user}.facebookProfile.last_import_time
            SET fb.avatar = {user}.facebookProfile.avatar
            MERGE (user)-[:FACEBOOK_PROFILE]->(fb)
            MERGE (fb)-[:PROFILE_OF]->(user)';
        }
        if (null !== $user->getTwitterProfile()) {
            if (null === $user->getTwitterProfile()->getUuid()) {
                $user->getTwitterProfile()->setUuid($this->uuid->getUuid());
            }
            $q .= 'MERGE (tw:TwitterProfile {id: {user}.twitterProfile.id})
            ON CREATE SET tw.screen_name = {user}.twitterProfile.screen_name,
            tw.name = {user}.twitterProfile.name,
            tw.uuid = {user}.twitterProfile.uuid
            SET tw.token = {user}.twitterProfile.token
            SET tw.last_import_time = {user}.twitterProfile.last_import_time
            SET tw.avatar = {user}.twitterProfile.avatar
            MERGE (user)-[:TWITTER_PROFILE]->(tw)
            MERGE (tw)-[:PROFILE_OF]->(user)';
        }
        $p = ['user' => $user->toArray()];
        $this->client->sendCypherQuery($q, $p);

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


    public function getUserConnectionsCount(User $user)
    {
        $q = 'MATCH (n:ActiveUser {uuid: {uuid}})
        OPTIONAL MATCH (n)-[:FACEBOOK_PROFILE|TWITTER_PROFILE]->(p)
        WITH (p)
        OPTIONAL MATCH (p)-[:FACEBOOK_FRIEND|FOLLOW_ON_TWITTER]->(other)
        RETURN count(distinct other) as connections';
        $p = [
            'uuid' => $user->getUuid()
        ];

        $result = $this->client->sendCypherQuery($q, $p)->getResult();

        return $result->get('connections');
    }

    public function searchActiveUser($term, $currentUser)
    {
        $q = 'MATCH (user:ActiveUser {uuid: {currentUser}.uuid}), (n:TwitterProfile)
        WHERE ( n.name =~ {term} OR n.screen_name =~ {term} )
        AND NOT (user)-[:TWITTER_PROFILE]->(n)
        RETURN n as suggestions, user
        UNION
        MATCH (user:ActiveUser {uuid: {currentUser}.uuid}), (n:FacebookProfile)
        WHERE ( n.first_name =~ {term} OR n.last_name =~ {term} OR n.name =~ {term} )
        AND NOT (user)-[:FACEBOOK_PROFILE]->(n)
        RETURN n as suggestions, user
        ';
        $p = ['term' => '(?i)'.$term.'.*', 'currentUser' => $currentUser->toArray()];

        $result = $this->client->sendCypherQuery($q, $p)->getResult();

        if (null === $result->get('suggestions')) {
            return array();
        }

        return $this->mapper->hydrateProfileCollection($result->get('suggestions', null, true));
    }

    public function getNodeById($id)
    {
        $q = 'MATCH (n) WHERE id(n) = {id} RETURN n';
        $p = ['id' => $id];

        $result = $this->client->sendCypherQuery($q, $p);

        return $result->getSingleNode();
    }

    public function getProfileByUuid($uuid)
    {
        $q = 'MATCH (profile {uuid:{uuid}})
        WHERE profile :FacebookProfile
        OR profile :TwitterProfile
        RETURN profile';
        $p = ['uuid' => $uuid];
        $result = $this->client->sendCypherQuery($q, $p)->getResult();
        if (null !== $result->get('profile')) {
            return $this->mapper->hydrateProfile($result->get('profile'));
        } else {
            return null;
        }
    }

    public function getBacon(User $user, SocialProfileInterface $profile)
    {
        $profileName = ucfirst($profile->getProfileName()).'Profile';
        $q = 'MATCH (user:ActiveUser {uuid: {user}.uuid})
        WITH user
        MATCH (profile:'.$profileName.' {uuid:{profile}.uuid})
        MATCH p=shortestPath((user)-[*]->(profile))
        WITH filter(x in nodes(p)
        WHERE NOT \'ActiveUser\' in labels(x)
        AND NOT ((user)-[:FACEBOOK_PROFILE|TWITTER_PROFILE]-(x))
        AND NOT x.uuid = {profile}.uuid
        ) as f
        RETURN length(f) as l';
        $p = ['user' => $user->toArray(), 'profile' => $profile->toArray()];
        $result = $this->client->sendCypherQuery($q, $p)->getResult();

        return $result->get('l');
    }

    public function getFacebookUserFromProfileId($id)
    {
        $q = 'MATCH (n) WHERE id(n) = {id}
        WITH n
        MATCH (n)<-[:FACEBOOK_PROFILE]-(user)
        RETURN user, n';
        $p = ['id' => $id];

        $result = $this->client->sendCypherQuery($q, $p);
        $user = $result->get('user');
        if (null !== $user) {
            return $this->mapper->hydrate($user, $result->get('n'));
        }

        return null;
    }

    public function getTwitterUserFromProfileId($id)
    {
        $q = 'MATCH (n) WHERE id(n) = {id}
        WITH n
        MATCH (n)<-[:TWITTER_PROFILE]-(user)
        RETURN user, n';
        $p = ['id' => $id];

        $result = $this->client->sendCypherQuery($q, $p);
        $user = $result->get('user');
        if (null !== $user) {
            return $this->mapper->hydrate($user, null, $result->get('n'));
        }

        return null;
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
        $user->setUuid($this->uuid->getUuid());
        $user->getTwitterProfile()->setUuid($this->uuid->getUuid());

        $q = 'MERGE (user:User {uuid: {user}.uuid})
        SET user :ActiveUser
        MERGE (twitter:TwitterProfile {id: {user}.twitterProfile.id})
        SET twitter.uuid = {user}.twitterProfile.uuid
        SET twitter.screen_name = {user}.twitterProfile.screen_name
        SET twitter.name = {user}.twitterProfile.name
        SET twitter.token = {user}.twitterProfile.token
        MERGE (user)-[:TWITTER_PROFILE]->(twitter)
        MERGE (twitter)-[:PROFILE_OF]->(user)
        RETURN user, twitter';
        $p = ['user' => $user->toArray()];

        $this->client->sendCypherQuery($q, $p);

        return $user;
    }

    private function createFacebookUser(User $user)
    {
        $user->setUuid($this->uuid->getUuid());
        $user->getFacebookProfile()->setUuid($this->uuid->getUuid());

        $q = 'MERGE (user:User {uuid: {user}.uuid})
        SET user :ActiveUser
        MERGE (facebook:FacebookProfile {id: {user}.facebookProfile.id})
        SET facebook.uuid = {user}.facebookProfile.uuid
        SET facebook.first_name = {user}.facebookProfile.first_name
        SET facebook.last_name = {user}.facebookProfile.last_name
        SET facebook.token = {user}.facebookProfile.token
        SET facebook.email = {user}.facebookProfile.email
        SET facebook.avatar = {user}.facebookProfile.avatar
        MERGE (user)-[:FACEBOOK_PROFILE]->(facebook)
        MERGE (facebook)-[:PROFILE_OF]->(user)
        RETURN user, facebook';
        $p = ['user' => $user->toArray()];

        $this->client->sendCypherQuery($q, $p);

        return $user;
    }
}