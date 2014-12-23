<?php

namespace GraphAware\UuidBundle\Service;

use Rhumsaa\Uuid\Uuid;

class UuidService
{
    public function getUuid()
    {
        return Uuid::uuid4()->toString();
    }
}