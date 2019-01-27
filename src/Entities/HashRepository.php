<?php

namespace Fridde\Entities;

use Carbon\Carbon;
use Fridde\CustomRepository;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Security\Authenticator;

class HashRepository extends CustomRepository
{
    public function findByPassword(string $password, array $criteria = []): ?Hash
    {
        $this->selectAllHashes();
        if(isset($criteria['category'])){
            $this->havingCategory($criteria['category']);
        }
        if(empty($criteria['accept_expired'])){
            $this->expiredAfterToday();
        }

        $dot_pos = strpos($password, Authenticator::OWNER_SEPARATOR);
        if(isset($criteria['owner_id'])){
            $this->havingOwnerId($criteria['owner_id']);
        } elseif($dot_pos !== false){
            $this->havingOwnerId(substr($password,0, $dot_pos));
        }
        $this->matchingPassword($password);

        $valid_hashes = $this->getSelection();
        if (count($valid_hashes) > 1) {
            throw new NException(Error::DATABASE_INCONSISTENT, ['Hashes', $password]);
        }
        if (count($valid_hashes) === 0) {
            return null;
        }

        return array_shift($valid_hashes);
    }

    public function findHashesThatExpireAfter($date, int $category = null, string $owner_id = null): array
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

    public function matchingPassword(string $pw): self
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($pw) {
                return password_verify($pw, $h->getValue());
            }
        );

        return $this;
    }

    public function havingCategory(int $category): self
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($category) {
                return $h->getCategory() === $category;
            }
        );

        return $this;
    }

    public function havingOwnerId(string $owner_id): self
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($owner_id) {
                return $h->getOwnerId() === $owner_id;
            }
        );

        return $this;

    }

    public function selectAllHashes(): self
    {
        $this->selection = $this->findAll();

        return $this;
    }

    public function expiredBeforeToday()
    {
        return $this->expiredBefore(Carbon::today());
    }

    public function expiredAfterToday(): self
    {
        return $this->expiredAfter(Carbon::today());
    }

    public function expiredBefore(Carbon $date): self
    {
        $this->selection = array_filter(
            $this->selection,
            function (Hash $h) use ($date) {
                return $h->expiredBefore($date);
            }
        );

        return $this;
    }

    public function expiredAfter(Carbon $date): self
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

    public function findYoungestValidVersion($owner_id, int $category): ?string
    {
        $potential_hashes = $this->findHashesThatExpireAfter(Carbon::now(), $category, $owner_id);
        usort($potential_hashes, [$this, 'compareHashByVersion']);
        /* @var Hash $youngest_hash */
        $youngest_hash = array_pop($potential_hashes);
        if (empty($youngest_hash) || empty($youngest_hash->getVersion())) {
            return null;
        }

        return $youngest_hash->getVersion();
    }


    private function compareHashByVersion(Hash $hash1, Hash $hash2): int
    {
        return $hash1->getVersion() <= $hash2->getVersion() ? -1 : 1;
    }


}
