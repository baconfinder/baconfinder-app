<?php

namespace TomSawyer\BaconFinder\AppBundle\Model;

use TomSawyer\BaconFinder\AppBundle\Model\FacebookProfile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class User implements UserInterface, EquatableInterface
{
    protected $uuid;

    protected $email;

    protected $firstname;

    protected $lastname;

    protected $facebookId;

    protected $twitterId;

    protected $twitterScreenName;

    protected $twitterName;

    protected $facebookToken;

    protected $twitterToken;

    protected $roles;

    protected $password;

    protected $salt;

    protected $resourceOwner;

    protected $reloaded;


    /**
     * Returns the username depending on the service owner
     *
     * @return mixed|null|string username
     */
    public function getUsername()
    {
        switch ($this->resourceOwner) {
            case 'facebook':
                return $this->getFirstname() . ' ' . $this->getLastname();
                break;
            case 'twitter':
                return $this->getTwitterScreenName();
                break;
            default:
                return null;
        }
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
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param mixed $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param mixed $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return mixed
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    /**
     * @param mixed $facebookId
     */
    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;
    }

    /**
     * @return mixed
     */
    public function getTwitterId()
    {
        return $this->twitterId;
    }

    /**
     * @param mixed $twitterId
     */
    public function setTwitterId($twitterId)
    {
        $this->twitterId = $twitterId;
    }

    /**
     * @return mixed
     */
    public function getTwitterScreenName()
    {
        return $this->twitterScreenName;
    }

    /**
     * @param mixed $twitterScreenName
     */
    public function setTwitterScreenName($twitterScreenName)
    {
        $this->twitterScreenName = $twitterScreenName;
    }

    /**
     * @return mixed
     */
    public function getTwitterName()
    {
        return $this->twitterName;
    }

    /**
     * @param mixed $twitterName
     */
    public function setTwitterName($twitterName)
    {
        $this->twitterName = $twitterName;
    }

    /**
     * @return mixed
     */
    public function getFacebookToken()
    {
        return $this->facebookToken;
    }

    /**
     * @param mixed $facebookToken
     */
    public function setFacebookToken($facebookToken)
    {
        $this->facebookToken = $facebookToken;
    }

    /**
     * @return mixed
     */
    public function getTwitterToken()
    {
        return $this->twitterToken;
    }

    /**
     * @param mixed $twitterToken
     */
    public function setTwitterToken($twitterToken)
    {
        $this->twitterToken = $twitterToken;
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