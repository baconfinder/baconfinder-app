<?php

namespace TomSawyer\BaconFinder\AppBundle\Model;

use TomSawyer\BaconFinder\AppBundle\Model\SocialProfileInterface;

class TwitterProfile implements SocialProfileInterface
{
    protected $twitterId;

    protected $uuid;

    protected $screenName;

    protected $name;

    protected $token;

    protected $lastImportTime;

    protected $avatar;

    public function __construct($twitterId)
    {
        $this->twitterId = $twitterId;
    }

    public function getUsername()
    {
        return '@'.$this->screenName;
    }

    /**
     * @return mixed
     */
    public function getTwitterId()
    {
        return $this->twitterId;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid()
    {
        return $this->uuid;
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
    public function getScreenName()
    {
        return $this->screenName;
    }

    /**
     * @param mixed $screenName
     */
    public function setScreenName($screenName)
    {
        $this->screenName = $screenName;
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

    public function setAvatar($v)
    {
        if (null !== $v) {
            $v = (string) $v;
            if ($this->avatar !== $v) {
                $this->avatar = $v;
            }
        }
    }

    public function getAvatar()
    {
        return $this->avatar;
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

    public function getProfileName()
    {
        return 'twitter';
    }

    public function __toString()
    {
        return $this->getName(). '('.$this->getUsername().')';
    }

    public function isActive()
    {
        if ($this->token !== null || $this->lastImportTime !== null) {
            return true;
        }

        return false;
    }

    public function toArray()
    {
        return array(
            'uuid' => $this->uuid,
            'id' => (int) $this->twitterId,
            'screen_name' => $this->screenName,
            'name' => $this->name,
            'token' => $this->token,
            'last_import_time' => $this->lastImportTime,
            'avatar' => $this->avatar
        );
    }


}