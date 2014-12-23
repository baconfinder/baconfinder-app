<?php

namespace TomSawyer\BaconFinder\AppBundle\Importer;

use TomSawyer\BaconFinder\AppBundle\Twitter\TwitterClient;
use TomSawyer\BaconFinder\AppBundle\Event\TwitterImportEvent;
use Neoxygen\NeoClient\Client;

class TwitterFriendsImporter
{
    protected $twitterClient;

    protected $neo4jClient;

    public function __construct(TwitterClient $twitterClient, Client $neo4jClient)
    {
        $this->twitterClient = $twitterClient;
        $this->neo4jClient = $neo4jClient;
    }

    public function onTwitterImport(TwitterImportEvent $event)
    {
        $user = $event->getUser();
        $this->importFriendsForUser($user->getTwitterId());
    }

    public function importFriendsForUser($userId)
    {
        $friends = $this->twitterClient->getFriends($userId);
        $q = 'MATCH (user:ActiveUser {twitterId: {user_id} })
        WITH user
        UNWIND {friends} as friend
        MERGE (followed:User {twitterId: friend.id})
        ON CREATE
        SET followed.twitterName = friend.name
        SET followed.twitterScreenName = friend.screenName
        MERGE (user)-[:CONNECT]->(followed)';

        $p = [
            'user_id' => (int) $userId,
            'friends' => $friends
        ];

        $this->neo4jClient->sendCypherQuery($q, $p)->getResult();
    }
}