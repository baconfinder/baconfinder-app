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
        print_r($userId);
        print_r($friends);
        $q = 'MATCH (twitter:TwitterProfile {id: {id}})
        RETURN twitter';

        $p = [
            'id' => (int) $userId,
            'friends' => $friends
        ];

        $result = $this->neo4jClient->sendCypherQuery($q, $p)->getResult();
        print_r($result->get('user'));
        exit();
    }
}