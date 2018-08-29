<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Fridde\CustomRepository;
use Fridde\Error\Error;
use Fridde\Error\NException;

class HashRepository extends CustomRepository
{
    public function findByPassword(
        string $password,
        int $cat,
        bool $accept_expired = false
    ): ?Hash {
        $this->selectAllHashes()->havingCategory($cat)->matchingPassword($password);
        if (!$accept_expired) {
            $this->expiredAfterToday();
        }

        $valid_hashes = $this->getSelection();
        if (count($valid_hashes) > 1) {
            throw new NException(Error::DATABASE_INCONSISTENT, ['Hashes', $cat.': '.$password]);
        }
        if (count($valid_hashes) === 0) {
            return null;
        }

        return array_shift($valid_hashes);
    }

    public function findHashesThatExpireAfter($date, int $category = null, string $owner_id = null)
    {
        if ($date instanceof Carbon) {
            $date = $date->toIso8601String();
        }
        $crit[] = ['gt', 'ExpiresAt', $date];
        $crit[] = null !== $category ? ['Category', $category] : [];
        $crit[] = null !== $owner_id ? ['Owner_id', $owner_id] : [];
        $crit = array_filter($crit);

        return $this->select($crit);
    }

    public function matchingPassword(string $pw)
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($pw) {
                return password_verify($pw, $h->getValue());
            }
        );

        return $this;
    }

    public function havingCategory(int $category)
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($category) {
                return $h->getCategory() === $category;
            }
        );

        return $this;
    }

    public function havingOwnerId(string $owner_id)
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($owner_id) {
                return $h->getCategory() === $owner_id;
            }
        );

        return $this;

    }

    public function selectAllHashes()
    {
        $this->selection = $this->findAll();

        return $this;
    }

    public function expiredBeforeToday()
    {
        return $this->expiredBefore(Carbon::today());
    }

    public function expiredAfterToday()
    {
        return $this->expiredAfter(Carbon::today());
    }

    public function expiredBefore(Carbon $date)
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($date) {
                return $h->expiredBefore($date);
            }
        );

        return $this;
    }

    public function expiredAfter(Carbon $date)
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($date) {
                $exp = $h->getExpiresAt();

                return empty($exp) ? false : $date->lte($exp);
            }
        );

        return $this;
    }

    public function findOldestValidVersion($owner_id, int $category)
    {
        $potential_hashes = $this->findHashesThatExpireAfter(Carbon::now(), $category, $owner_id);
        usort($potential_hashes, [$this, 'compareHashByVersion']);
        /* @var Hash $oldest_hash */
        $oldest_hash = array_pop($potential_hashes);
        if (empty($oldest_hash) || empty($oldest_hash->getVersion())) {
            return null;
        }

        return $oldest_hash->getVersion();
    }


    private function compareHashByVersion(Hash $hash1, Hash $hash2): int
    {
        return $hash1->getVersion() <= $hash2->getVersion() ? -1 : 1;
    }


}
