<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Doctrine\ORM\Mapping AS ORM;

/**
 * @ORM\Entity(repositoryClass="Fridde\Entities\HashRepository")
 * @ORM\Table(name="hashes")
 * @ORM\HasLifecycleCallbacks
 */
class Hash
{
    /** @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /** @ORM\Column(type="smallint") */
    protected $Category;

    /** @ORM\Column(type="string", nullable=true) */
    protected $Version;

    /** @ORM\Column(type="string") */
    protected $Value;

    /** @ORM\Column(type="string") */
    protected $Owner_id;

    /** @ORM\Column(type="string", nullable=true) */
    protected $ExpiresAt;

    /** @ORM\Column(type="string", nullable=true) */
    protected $CreatedAt;

    public const CATEGORY_USER_URL_CODE = 0;
    public const CATEGORY_USER_COOKIE_KEY = 1;
    public const CATEGORY_SCHOOL_COOKIE_KEY = 2;
    public const CATEGORY_SCHOOL_PW = 3;
    public const CATEGORY_VISIT_CONFIRMATION_CODE = 4;
    public const CATEGORY_USER_REMOVAL_CODE = 5;

    public const RIGHTS_NO_SCHOOLS = 0;
    public const RIGHTS_SCHOOL_ONLY = 1;
    public const RIGHTS_ALL_SCHOOLS = 2;

    public function getId()
    {
        return $this->id;
    }

    public function getVersion(): ?string
    {
        return $this->Version;
    }

    /**
     * @param mixed $Version
     */
    public function setVersion($Version): void
    {
        $this->Version = $Version;
    }


    public function getValue()
    {
        return $this->Value;
    }

    public function setValue($Value)
    {
        $this->Value = $Value;

    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->Category;
    }

    /**
     * @param mixed $Category
     */
    public function setCategory(int $Category)
    {
        $this->Category = $Category;
    }

    /**
     * @return mixed
     */
    public function getOwnerId()
    {
        return $this->Owner_id;
    }

    /**
     * @param mixed $Owner_id
     */
    public function setOwnerId($Owner_id): void
    {
        $this->Owner_id = $Owner_id;
    }

    /**
     * @return mixed
     */
    public function getExpiresAt(): ?Carbon
    {
        if (empty($this->ExpiresAt)) {
            return null;
        }

        return Carbon::parse($this->ExpiresAt);
    }

    /**
     * @param mixed $ExpiresAt
     */
    public function setExpiresAt($ExpiresAt): void
    {
        if ($ExpiresAt instanceof Carbon) {
            $ExpiresAt = $ExpiresAt->toIso8601String();
        }

        $this->ExpiresAt = $ExpiresAt;
    }

    public function expiredBefore(Carbon $date): bool
    {
        $expiration = $this->getExpiresAt();
        if (empty($expiration)) {
            return true;
        }

        return $expiration->lt($date);
    }

    public function isExpired(): bool
    {
        return $this->expiredBefore(Carbon::now());
    }

    public function getCreatedAt()
    {
        if (empty($this->CreatedAt)) {
            return null;
        }

        return Carbon::parse($this->CreatedAt);
    }

    public function setCreatedAt($CreatedAt)
    {
        if ($CreatedAt instanceof Carbon) {
            $CreatedAt = $CreatedAt->toIso8601String();
        }
        $this->CreatedAt = $CreatedAt;
    }


    /** @ORM\PrePersist */
    public function prePersist()
    {
        $this->setCreatedAt(Carbon::now());
    }

    /** @ORM\PreUpdate */
    public function preUpdate()
    {
    }

    /** @ORM\PreRemove */
    public function preRemove()
    {
    }


}
