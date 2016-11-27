<?php
namespace Fridde\Entities;

class Topic extends Entity
{
    public function isLektion()
    {
        return trim($this->pick("IsLektion")) == "true";
    }
}
