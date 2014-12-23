<?php

namespace TomSawyer\BaconFinder\AppBundle\Importer;

use TomSawyer\BaconFinder\AppBundle\Twitter\TwitterClient;
use TomSawyer\BaconFinder\AppBundle\Event\TwitterImportEvent;
use GraphAware\UuidBundle\Service\UuidService;
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

    public function onTwitterImport(TwitterImportEvent $event)
    {
        $user = $event->getUser();
        $this->importFriendsForUser($user->getTwitterId());
    }

    public function importFriendsForUser($userId)
    {
        $response = $this->twitterClient->getFriends($userId);
        $friends = [];
        foreach ($response as $friend) {
            $friend['uuid'] = $this->uuid->getUuid();
            $friends[] = $friend;
        }
        $q = 'MATCH (user:ActiveUser {twitterId: {user_id} })
        WITH user
        UNWIND {friends} as friend
        MERGE (followed:User {twitterId: friend.id})
        ON CREATE
        SET followed.twitterName = friend.name, followed.twitterScreenName = friend.screenName,
        followed.uuid = friend.uuid
        MERGE (user)-[:CONNECT]->(followed)';

        $p = [
            'user_id' => (int) $userId,
            'friends' => $friends
        ];

        $this->neo4jClient->sendCypherQuery($q, $p)->getResult();
    }
}