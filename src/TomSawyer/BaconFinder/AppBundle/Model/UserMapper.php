<?php

namespace TomSawyer\BaconFinder\AppBundle\Model;

use TomSawyer\BaconFinder\AppBundle\Model\User,
    TomSawyer\BaconFinder\AppBundle\Model\FacebookProfile,
    TomSawyer\BaconFinder\AppBundle\Model\TwitterProfile;

class UserMapper
{
    public function hydrate($userNode, $facebook = null, $twitter = null)
    {
        $user = $this->hydrateUser($userNode);

        if (null !== $facebook) {
            $user->setFacebookProfile($this->hydrateFacebook($facebook));
        }

        if (null !== $twitter) {
            $user->setTwitterProfile($this->hydrateTwitter($twitter));
        }

        return $user;
    }

    public function hydrateProfileCollection(array $collection)
    {
        $profiles = [];
        foreach ($collection as $elt) {
            $profiles[] = $this->hydrateProfile($elt);
        }

        return $profiles;
    }

    public function hydrateProfile($node)
    {
        if ($node->hasLabel('FacebookProfile')) {

            return $this->hydrateFacebook($node);
        } elseif ($node->hasLabel('TwitterProfile')) {

            return $this->hydrateTwitter($node);
        }
    }

    private function hydrateUser($node)
    {
        $user = new User();
        $user->setUuid($node->getProperty('uuid'));
        if ($node->hasLabel('ActiveUser')) {
            $user->setActive();
        }

        return $user;
    }

    private function hydrateFacebook($node)
    {
        $profile = new FacebookProfile((int) $node->getProperty('id'));
        $profile->setUuid($node->getProperty('uuid'));
        if ($node->hasProperty('first_name')) {
            $profile->setFirstname($node->getProperty('first_name'));
            $profile->setLastname($node->getProperty('last_name'));
            $profile->setEmail($node->getProperty('email'));
        } else {
            $profile->setName($node->getProperty('name'));
        }
        if ($node->hasProperty('last_import_time')) {
            $t = new \DateTime();
            $t->setTimestamp($node->getProperty('last_import_time'));
            $profile->setLastImportTime($t);
        }
        if ($node->hasProperty('avatar')) {
            $profile->setAvatar($node->getProperty('avatar'));
        }

        return $profile;
    }

    private function hydrateTwitter($node)
    {
        $profile = new TwitterProfile((int) $node->getProperty('id'));
        $profile->setUuid($node->getProperty('uuid'));
        $profile->setScreenName($node->getProperty('screen_name'));
        $profile->setName($node->getProperty('name'));
        if ($node->hasProperty('last_import_time')) {
            $t = new \DateTime();
            $t->setTimestamp($node->getProperty('last_import_time'));
            $profile->setLastImportTime($t);
        }
        if ($node->hasProperty('avatar')) {
            $profile->setAvatar($node->getProperty('avatar'));
        }

        return $profile;
    }
}