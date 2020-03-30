<?php
namespace Fridde\Entities;

use Carbon\Carbon;
use Fridde\CustomRepository;

class MessageRepository extends CustomRepository
{

    public function findByProperties($properties)
    {
        return array_filter($this->findAll(), function(Message $m) use ($properties){
            return $m->checkProperties($properties);
        });
    }

    public function findMessagesOlderThan(Carbon $date)
    {
        $criteria = ['lt', 'Timestamp', $date->toDateString()];
        return $this->select($criteria);
    }

    public function getSentWelcomeMessages()
    {
        $criteria = [['eq', 'Subject', Message::SUBJECT_WELCOME_NEW_USER]];
		$criteria[] = ['eq', 'Carrier', Message::CARRIER_MAIL];
        return $this->select($criteria);
    }

}
