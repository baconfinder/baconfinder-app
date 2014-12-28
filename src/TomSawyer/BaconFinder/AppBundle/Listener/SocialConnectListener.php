<?php

namespace TomSawyer\BaconFinder\AppBundle\Listener;

use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use TomSawyer\BaconFinder\AppBundle\Event\SocialConnectEvent;
use Symfony\Component\Security\Core\User\UserInterface;
use TomSawyer\BaconFinder\AppBundle\Importer\SocialImporter;

/**
 * Class SocialConnectListener
 *
 * Listens to the "SOCIAL_CONNECT" event and determines if the friendships import should be done.
 * If yes, the SocialImporter is triggered
 */
class SocialConnectListener
{
    /**
     * @var
     */
    protected $importFrequency;

    protected $importer;

    /**
     * @param $importFrequency
     */
    public function __construct($importFrequency, SocialImporter $importer)
    {
        $this->importFrequency = $importFrequency;
        $this->importer = $importer;
    }

    /**
     * Determines if the import should be done based on two params
     * 1. If it is a joined account
     * 2. Based on the last import time
     *
     * @param SocialConnectEvent $event
     * @return null|void
     */
    public function onSocialConnect(SocialConnectEvent $event)
    {
        $now = New \DateTime("NOW");
        $t = $now->getTimestamp();
        $max = $t - $this->importFrequency;

        if ($event->isJoinedAccount()) {
            if (null === $event->getResponse()) {

                throw new \RuntimeException('A joined account must be set with a OAuth Response');
            }

            return $this->doImport($event->getUser(), $event->getResponse());
        }

        $getter = 'get'.ucfirst(strtolower($event->getResponse()->getResourceOwner()->getName())).'Profile';
        $lastImport = $event->getUser()->$getter()->getLastImportTime();

        if ($lastImport <= $max) {

            return $this->doImport($event->getUser(), $event->getResponse());
        }

        return null;

    }

    /**
     * @param UserInterface $user
     * @param UserResponseInterface $response
     */
    private function doImport(UserInterface $user, UserResponseInterface $response)
    {
        $this->importer->import($user, $response);
    }
}