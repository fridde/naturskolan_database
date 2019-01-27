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
        $c = [];

        if(isset($criteria['category'])){
            $c[] = ['eq', 'Category', $criteria['category']];
        }
        if(empty($criteria['accept_expired'])){
            $c[] = ['gte', 'ExpiresAt', Carbon::today()];
        }

        $dot_pos = strpos($password, Authenticator::OWNER_SEPARATOR);
        $owner_id = $criteria['owner_id'] ?? substr($password,0, $dot_pos);

        if($owner_id !== '' || isset($criteria['owner_id'])){
            $c[] = ['eq', 'Owner_id', $owner_id];
        }
        $hashes = $this->selectAnd($c);

        return $this->matchPassword($hashes, $password);
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

    private function matchPassword(array $hashes, string $pw): ?Hash
    {
        $hashes = array_filter(
            $hashes,
            function (Hash $h) use ($pw) {
                return password_verify($pw, $h->getValue());
            }
        );

        if (count($hashes) > 1) {
            throw new NException(Error::DATABASE_INCONSISTENT, ['Hashes', $pw]);
        }

        return array_shift($hashes);
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
