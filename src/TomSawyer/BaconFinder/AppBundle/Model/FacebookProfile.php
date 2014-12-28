<?php

namespace TomSawyer\BaconFinder\AppBundle\Model;

use TomSawyer\BaconFinder\AppBundle\Model\SocialProfileInterface;

class FacebookProfile implements SocialProfileInterface
{
    protected $uuid;

    protected $id;

    protected $firstname;

    protected $lastname;

    protected $email;

    protected $token;

    protected $name;

    protected $lastImportTime;

    public function __construct($id, $email = null, $firstname = null, $lastname = null, $name = null)
    {
        $this->id = $id;
        $this->email = $email;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getUsername()
    {
        if ($this->isActive()) {
            return $this->firstname . ' ' . $this->lastname;
        }

        return $this->name;
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
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    public function getLastImportTime()
    {
        return $this->lastImportTime;
    }

    public function setLastImportTime(\DateTime $time)
    {
        $this->lastImportTime = $time->getTimestamp();
    }

    public function isActive()
    {
        if(null !== $this->email) {
            return true;
        }

        return false;
    }

    public function getProfileName()
    {
        return 'facebook';
    }

    public function __toString()
    {
        if (null !== $this->firstname) {
            return $this->firstname.' '.$this->lastname;
        }

        return $this->name;
    }

    public function toArray()
    {
        return array(
            'uuid' => $this->uuid,
            'id' => (int) $this->id,
            'first_name' => $this->firstname,
            'last_name' => $this->lastname,
            'email' => $this->email,
            'token' => $this->token,
            'name' => $this->name,
            'last_import_time' => $this->lastImportTime
        );
    }
}