<?php

namespace TomSawyer\BaconFinder\AppBundle\Model;

use TomSawyer\BaconFinder\AppBundle\Model\SocialProfileInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class User implements UserInterface, EquatableInterface
{
    protected $uuid;

    protected $roles;

    protected $password;

    protected $salt;

    protected $resourceOwner;

    protected $facebookProfile;

    protected $twitterProfile;

    protected $activeUser;

    /**
     * Returns the username depending on the service owner
     *
     * @return mixed|null|string username
     */
    public function getUsername()
    {
        if (null === $this->resourceOwner) {
            throw new \InvalidArgumentException('The User is not bounded to a social context');
        }

        $profileName = 'get'.ucfirst($this->resourceOwner).'Profile';

        return $this->$profileName()->getUsername();
    }

    /**
     * @return mixed
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return mixed
     */
    public function getRoles()
    {
        return array(
            'ROLE_USER'
        );
    }

    /**
     * @param mixed $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param mixed $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return mixed
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param mixed $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return mixed
     */
    public function getResourceOwner()
    {
        return $this->resourceOwner;
    }

    /**
     * @param mixed $resourceOwner
     */
    public function setResourceOwner($resourceOwner)
    {
        $this->resourceOwner = $resourceOwner;
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    public function eraseCredentials()
    {

    }

    /**
     * @return \TomSawyer\BaconFinder\AppBundle\Model\FacebookProfile
     */
    public function getFacebookProfile()
    {
        return $this->facebookProfile;
    }

    /**
     * @param mixed $facebookProfile
     */
    public function setFacebookProfile(SocialProfileInterface $facebookProfile)
    {
        $this->facebookProfile = $facebookProfile;
    }

    /**
     * @return mixed
     */
    public function getTwitterProfile()
    {
        return $this->twitterProfile;
    }

    /**
     * @param mixed $twitterProfile
     */
    public function setTwitterProfile(SocialProfileInterface $twitterProfile)
    {
        $this->twitterProfile = $twitterProfile;
    }

    public function addSocialProfile(SocialProfileInterface $profile)
    {
        $profileName = ucfirst($profile->getName()).'Profile';
        $getter = 'get'.$profileName;
        $setter = 'set'.$profileName;

        if (null !== $getter()) {
            throw new \InvalidArgumentException(sprintf('The user "%s" has already a "%s"', $this->getUsername(), $profileName));
        }
        $setter($profile);

        return $this;
    }

    public function isActive()
    {
        return null !== $this->activeUser;
    }

    public function setActive()
    {
        $this->activeUser = true;
    }

    public function toArray()
    {
        $user = [
            'uuid' => $this->uuid
        ];
        if (null !== $this->facebookProfile) {
            $user['facebookProfile'] = $this->facebookProfile->toArray();
        }
        if (null !== $this->twitterProfile) {
            $user['twitterProfile'] = $this->twitterProfile->toArray();
        }

        return $user;
    }
}