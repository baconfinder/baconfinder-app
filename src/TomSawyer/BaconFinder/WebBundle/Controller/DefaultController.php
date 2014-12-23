<?php

namespace TomSawyer\BaconFinder\WebBundle\Controller;

use Neoxygen\NeoClient\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="home")
     * @Template()
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route("/account", name="my_account")
     * @Template()
     */
    public function myAccountAction()
    {
        return array();
    }

    /**
     * @Route("/account/connections-count", name="user_connection_count")
     * @Template()
     */
    public function connectionsCountAction()
    {
        $user = $this->getUser();
        $count = $this->container->get('tom_sawyer.bacon_finder.user_repository')->getUserConnectionsCount($user);

        return array(
            'count' => $count
        );
    }

    /**
     * @Route("/account/friend/search", name="search_friend_like")
     * @Method("POST")
     */
    public function searchFriend(Request $request)
    {
        $name = $request->request->get('term');
        $response = new JsonResponse();

        try {
            $suggestions = $this->container->get('tom_sawyer.bacon_finder.user_repository')->searchActiveUser(
                $name
            );
            $users = [];
            foreach ($suggestions as $suggestion) {
                if ($suggestion->hasProperty('firstname')) {
                    $users[] = [
                        'label' => $suggestion->getProperty('firstname'). ' ' . $suggestion->getProperty('lastname'),
                        'value' => $suggestion->getProperty('uuid')
                ];
                } elseif($suggestion->hasProperty('twitterName')) {
                    $users[] = [
                        'label' => $suggestion->getProperty('twitterName'). ' (@' . $suggestion->getProperty('twitterScreenName').')',
                        'value' => $suggestion->getProperty('uuid')
                    ];
                } elseif($suggestion->hasProperty('name')) {
                    $users[] = [
                        'label' => $suggestion->getProperty('name'),
                        'value' => $suggestion->getProperty('uuid')
                    ];
                }
            }

            $response->setData($users);
            $response->setStatusCode(200);

            return $response;
        } catch (HttpException $e) {
            $response->setStatusCode($e->getCode());
            $response->setData([
                'error' => $e->getMessage()
            ]);

            return $response;
        }
    }

    /**
     * @Route("/account/user-info/{uuid}", name="user_info")
     * @Template()
     */
    public function userInfoAction($uuid, Request $request)
    {
        $userRepository = $this->get('tom_sawyer.bacon_finder.user_repository');
        $user = $userRepository->getUserInfo($uuid);

        return array(
            'user' => $this->getUserFromNode($user)
        );
    }

    private function getUserFromNode($node)
    {
        $user = [];
        if ($node->hasLabel('ActiveUser')) {
            $user['active'] = true;
            if ($node->hasProperty('firstname')) {
                $user['facebook'] = [
                    'firstname' => $node->getProperty('firstname'),
                    'lastname' => $node->getProperty('lastname'),
                    'email' => $node->getProperty('email')
                ];
            }
            if ($node->hasProperty('twitterId')) {
                $user['twitter'] = [
                    'name' => $node->getProperty('twitterName'),
                    'screenName' => $node->getProperty('twitterScreenName')
                ];
            }
        } else {
            $user['active'] = false;
            if ($node->hasProperty('name')) {
                $user['name'] = $node->getProperty('name');
            } else {
                $user['name'] = $node->getProperty('twitterName');
            }
        }

        return $user;

    }
}
