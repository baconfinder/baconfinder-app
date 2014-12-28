<?php

namespace TomSawyer\BaconFinder\AppBundle\Importer;

use GraphAware\UuidBundle\Service\UuidService;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Neoxygen\NeoClient\Client;
use Neoxygen\NeoClient\Exception\HttpException;
use Symfony\Component\Security\Core\User\UserInterface;
use TomSawyer\BaconFinder\AppBundle\Facebook\Facebook;
use TomSawyer\BaconFinder\AppBundle\Manager\UserManager;

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

    public function importFriends(UserResponseInterface $response)
    {

        $friends = $this->getFriends($response->getAccessToken());
        $this->importFacebookFriends($response->getUsername(), $friends);
    }

    private function getFriends($token)
    {
        $friends = $this->facebookClient->getUserFriends($token);
        $newFriends = [];
        foreach ($friends as $friend) {
            $friend['id'] = (int) $friend['facebookId'];
            $friend['uuid'] = $this->uuid->getUuid();
            $newFriends[] = $friend;
        }

        return $newFriends;
    }

    private function importFacebookFriends($facebookId, array $friends)
    {
        if (count($friends) <= 0) {

            return null;
        }

        $q = 'MATCH (fb:FacebookProfile {id: {id}})
        UNWIND {friends} as friend
        MERGE (fbf:FacebookProfile {id: friend.id})
        ON CREATE SET fbf.name = friend.name, fbf.uuid = friend.uuid
        MERGE (fb)-[:FACEBOOK_FRIEND]->(fbf)';
        $p = [
            'id' => (int) $facebookId,
            'friends' => $friends
        ];
        try {
            $this->neo4jClient->sendCypherQuery($q, $p);
        } catch (HttpException $e) {

        }

    }
}