<?php

namespace TomSawyer\BaconFinder\AppBundle\Model;

interface SocialProfileInterface
{
    public function getUsername();

    public function toArray();
}