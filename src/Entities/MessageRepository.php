<?php
namespace Fridde\Entities;

use Fridde\CustomRepository;

class MessageRepository extends CustomRepository
{

    public function findByProperties($properties)
    {
        return array_filter($this->findAll(), function($m) use ($properties){
            return $m->checkProperties($properties);
        });
    }

    public function findMessagesOlderThan($date)
    {
        $criteria = ['lt', 'Timestamp', $date->toIso8601String()];
        return $this->select($criteria);
    }

    public function getSentWelcomeMessages()
    {
        $criteria = [['eq', 'Subject', Message::SUBJECT_WELCOME_NEW_USER]];
        $criteria[] = ['eq', 'Status', Message::STATUS_SENT];
        return $this->select($criteria);
    }

}
