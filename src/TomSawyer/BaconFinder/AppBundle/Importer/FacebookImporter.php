<?php

namespace TomSawyer\BaconFinder\AppBundle\Importer;

use Neoxygen\NeoClient\Client;
use Neoxygen\NeoClient\Exception\HttpException;
use GraphAware\UuidBundle\Service\UuidService;
use TomSawyer\BaconFinder\AppBundle\Facebook\Facebook;
use TomSawyer\BaconFinder\AppBundle\Event\FacebookImportEvent;

class FacebookImporter
{
    protected $neo4jClient;

    protected $facebookClient;

    protected $uuid;

    public function __construct(Facebook $fbClient, Client $neo, UuidService $uuid)
    {
        $this->neo4jClient = $neo;
        $this->facebookClient = $fbClient;
        $this->uuid = $uuid;
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
        $newFriends = [];
        foreach ($friends as $friend) {
            $friend['uuid'] = $this->uuid->getUuid();
            $friend['id'] = (int) $friend['facebookId'];
            $newFriends[] = $friend;
        }
        $q = 'MATCH (user:ActiveUser {facebookId: {id}})
        UNWIND {friends} as friend
        MERGE (fr:User {facebookId: friend.id})
        ON CREATE SET fr.name = friend.name, fr.uuid = friend.uuid
        MERGE (user)-[:CONNECT]->(fr)';
        $p = [
            'id' => (int) $user->getFacebookId(),
            'friends' => $newFriends
        ];
        try {
            $this->neo4jClient->sendCypherQuery($q, $p);
        } catch (HttpException $e) {

        }

    }
}