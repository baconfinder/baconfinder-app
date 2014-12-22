<?php

namespace TomSawyer\BaconFinder\AppBundle\Importer;

use Neoxygen\NeoClient\Client;
use Neoxygen\NeoClient\Exception\HttpException;
use TomSawyer\BaconFinder\AppBundle\Facebook\Facebook;
use TomSawyer\BaconFinder\AppBundle\Event\FacebookImportEvent;

class FacebookImporter
{
    protected $neo4jClient;

    protected $facebookClient;

    public function __construct(Facebook $fbClient, Client $neo)
    {
        $this->neo4jClient = $neo;
        $this->facebookClient = $fbClient;
    }

    public function onFacebookImport(FacebookImportEvent $event)
    {
        $friends = $this->facebookClient->getUserFriends($event->getToken());
        if (!empty($friends)) {
            $this->importFacebookFriends($event->getUser(), $friends);
        }
    }

    private function importFacebookFriends($user, array $friends)
    {
        $q = 'MATCH (user:ActiveUser {facebookId: {id}})
        UNWIND {friends} as friend
        MERGE (fr:User {facebookId: friend.facebookId})
        ON CREATE SET fr.name = friend.name
        MERGE (user)-[:CONNECT]->(fr)';
        $p = [
            'id' => (int) $user->getFacebookId(),
            'friends' => $friends
        ];
        try {
            $this->neo4jClient->sendCypherQuery($q, $p);
        } catch (HttpException $e) {
            
        }

    }
}