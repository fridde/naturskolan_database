<?php

namespace Fridde\Entities;

use Fridde\CustomRepository;

class NoteRepository extends CustomRepository
{
    public function findByVisitAndAuthor(int $visit_id, int $author_id): ?Note
    {
        $crit['Visit'] = $visit_id;
        $crit['User'] = $author_id;

        return $this->findOneBy($crit);
    }
}
