<?php

namespace TomSawyer\BaconFinder\AppBundle\Model;

use TomSawyer\BaconFinder\AppBundle\Model\FacebookProfile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class User implements UserInterface, EquatableInterface
{
    protected $uuid;

    protected $email;

    protected $facebookProfile;

    protected $twitterProfile;

    protected $roles;

    protected $password;

    protected $salt;

    protected $resourceOwner;

    protected $reloaded;

    /**
     * @return mixed
     */
    public function getUsername()
    {
        $profileM = 'get' . ucfirst($this->resourceOwner) . 'Profile';
        $profile = $this->$profileM();

        return $profile->getUsername();
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
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param mixed $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return \BaconFinder\AppBundle\Model\FacebookProfile
     */
    public function getFacebookProfile()
    {
        return $this->facebookProfile;
    }

    /**
     * @param mixed $facebookProfile
     */
    public function setFacebookProfile(FacebookProfile $facebookProfile)
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
    public function setTwitterProfile($twitterProfile)
    {
        $this->twitterProfile = $twitterProfile;
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
     * @return mixed
     */
    public function getRoles()
    {
        return array(
            'ROLE_USER'
        );
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

    public function shouldBeReloaded()
    {
        $reloadedCount = (null != $this->reloaded) ? $this->reloaded : 0;
        if (10 <= $reloadedCount) {
            $this->incReloaded();
            return true;
        }

        return false;
    }

    public function incReloaded()
    {
        if (null != $this->reloaded || 0 < $this->reloaded) {
            $this->reloaded++;

            return $this;
        }

        $this->reloaded = 1;
    }
}