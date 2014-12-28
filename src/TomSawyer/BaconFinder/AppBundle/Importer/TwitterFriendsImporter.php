<?php

namespace TomSawyer\BaconFinder\AppBundle\Importer;

use GraphAware\UuidBundle\Service\UuidService;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use TomSawyer\BaconFinder\AppBundle\Twitter\TwitterClient;
use Neoxygen\NeoClient\Client;

class TwitterFriendsImporter
{
    protected $twitterClient;

    protected $neo4jClient;

    protected $uuid;

    public function __construct(TwitterClient $twitterClient, Client $neo4jClient, UuidService $uuid)
    {
        $this->twitterClient = $twitterClient;
        $this->neo4jClient = $neo4jClient;
        $this->uuid = $uuid;
    }

    public function importFriends(UserResponseInterface $response)
    {
        $friends = $this->getFriends($response->getUsername());
        $this->importFriendsForUser($response->getUsername(), $friends);
    }

    public function importFriendsForUser($userId, $friends)
    {

        $q = 'MATCH (tw:TwitterProfile {id: {id} })
        WITH tw
        UNWIND {friends} as friend
        MERGE (twf:TwitterProfile {id: friend.id})
        ON CREATE
        SET twf.name = friend.name, twf.screen_name = friend.screenName, twf.uuid = friend.uuid
        MERGE (tw)-[:FOLLOW_ON_TWITTER]->(twf)';

        $p = [
            'id' => (int) $userId,
            'friends' => $friends
        ];

        $this->neo4jClient->sendCypherQuery($q, $p)->getResult();
    }

    private function getFriends($userId)
    {
        $response = $this->twitterClient->getFriends($userId);
        $friends = [];
        foreach ($response as $friend) {
            $friend['twitterId'] = (int) $friend['id'];
            $friend['uuid'] = $this->uuid->getUuid();
            $friends[] = $friend;
        }

        return $friends;
    }
}