<?php

namespace TomSawyer\BaconFinder\WebBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

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
}
