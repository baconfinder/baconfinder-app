<?php

namespace TomSawyer\BaconFinder\AppBundle\Twitter;

use GuzzleHttp\Client,
    GuzzleHttp\Subscriber\Oauth\Oauth1;
use GuzzleHttp\Exception\ClientException;

class TwitterClient
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var
     */
    protected $apiKey;

    /**
     * @var
     */
    protected $apiSecret;

    /**
     * @var
     */
    protected $appToken;

    /**
     * @var
     */
    protected $appTokenSecret;

    /**
     * @param $apiKey
     * @param $apiSecret
     * @param $appToken
     * @param $appTokenSecret
     */
    public function __construct($apiKey, $apiSecret, $appToken, $appTokenSecret)
    {
        $this->client = new Client([
            'base_url' => 'https://api.twitter.com/1.1/',
            'defaults' => ['auth' => 'oauth']
        ]);
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->appToken = $appToken;
        $this->appTokenSecret = $appTokenSecret;
    }

    /**
     * Get friend list of the specified user
     *
     * @param int $userId
     * @return array $friendsList the list of followed users
     */
    public function getFriends($userId)
    {
        $friendsList = [];
        $cursor = -1;
        while ($cursor != null) {
            $response = $this->processRequest($userId, $cursor);
            foreach ($response['users'] as $friend) {
                $friendsList[] = [
                    'id' => $friend['id'],
                    'screenName' => $friend['screen_name'],
                    'name' => $friend['name'],
                    'desc' => $friend['description']
                ];
            }
            $cursor = (int) $response['next_cursor'];
        }

        return $friendsList;
    }

    /**
     * @param $userId
     * @param null $cursor
     * @return mixed
     */
    private function processRequest($userId, $cursor = null)
    {
        $oauth = new Oauth1(
            [
                'consumer_key' => $this->apiKey,
                'consumer_secret' => $this->apiSecret,
                'token' => 	$this->appToken,
                'token_secret' => $this->appTokenSecret
            ]
        );
        $this->client->getEmitter()->attach($oauth);
        $cursor = null !== $cursor ? $cursor : -1;

        $request = $this->client->createRequest('GET', 'friends/list.json');
        $query = $request->getQuery();
        $query->set('user_id', $userId);
        $query->set('cursor', $cursor);
        $query->set('count', 200);
        $query->set('skip_status', 'true');
        $query->set('include_user_entities', 'false');

        try {
            return $this->client->send($request)->json();
        } catch (ClientException $e) {
            print_r((string) $e->getResponse());
            exit();
            //throw new \RuntimeException($e->getMessage());
        }

    }
}