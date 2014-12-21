<?php

namespace TomSawyer\BaconFinder\AppBundle\Importer;

use TomSawyer\BaconFinder\AppBundle\Twitter\TwitterClient;
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

    public function importFriendsForUser($userId)
    {
        $friends = $this->twitterClient->getFriends($userId);
        $q = 'MATCH (twitter:TwitterProfile {id: {user_id} })
        WITH twitter
        UNWIND {friends} as friend
        MERGE (followed:TwitterProfile {id: friend.id} )
        MERGE (twitter)-[:FOLLOWS]->(followed)
        RETURN twitter';

        $p = [
            'user_id' => (int) $userId,
            'friends' => $friends
        ];

        $result = $this->neo4jClient->sendCypherQuery($q, $p)->getResult();
    }
}