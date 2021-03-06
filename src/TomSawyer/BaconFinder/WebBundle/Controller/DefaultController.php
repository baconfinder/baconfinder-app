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
        $user = $this->getUser();
        if (null !== $user->getFacebookProfile()) {
            
        }

        try {
            $suggestions = $this->container->get('tom_sawyer.bacon_finder.user_repository')->searchActiveUser(
                $name,
                $user
            );
            $users = [];
            foreach ($suggestions as $suggestion) {
                $user = [
                    'value' => $suggestion->getUuid(),
                    'label' => $suggestion->__toString()
                ];
                $users[] = $user;
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
     * @Route("/account/profile-info/{uuid}", name="user_info")
     * @Template()
     */
    public function userInfoAction($uuid, Request $request)
    {
        $userRepository = $this->get('tom_sawyer.bacon_finder.user_repository');
        $profile = $userRepository->getProfileByUuid($uuid);

        if (!$profile) {
            throw new \InvalidArgumentException(sprintf('Profile with UUID "%s" not found', $uuid));
        }

        $bacon = $userRepository->getBacon($this->getUser(), $profile);

        return array(
            'profile' => $profile,
            'bacon' => (int) $bacon
        );
    }
}
