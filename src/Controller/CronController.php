<?php

namespace Fridde\Controller;

use Fridde\{Naturskolan, Utility as U, Task};
use Carbon\Carbon;

class CronController {

    private $N;
    private $CRON_SETTINGS;
    private $delay;
    private $slot_duration;
    private $intervals;
    private $slot_counter;
    private $slot_time;

    public function __construct()
    {
        $this->N = new Naturskolan();
        $this->CRON_SETTINGS = $GLOBALS["SETTINGS"]["cronjobs"];
        $this->delay = $this->CRON_SETTINGS["delay"];
        $this->slot_duration = $this->CRON_SETTINGS["slot_duration"];
        $this->intervals = $this->CRON_SETTINGS["intervals"];
    }

    public function execute($params = [])
    {
        $this->slot_counter = $params["counter"] ?? $this->N->getStatus("slot_counter");

        $this->setSlotTime();
        $this->resetIfMonday();

        foreach(array_keys($this->intervals) as $task_type){
            if($this->checkIfRightTime($task_type)){
        		$task = new Task($task_type);
        		$task->execute();
        	}
        }
        $this->N->setStatus("slot_counter", $this->slot_counter + 1);
    }

    public function resetIfMonday()
    {
        $has_gone_one_day = U::divideDuration($this->slot_time, [1, "d"]) > 1.0;
        $is_monday = Carbon::today()->dayOfWeek === 1;
        if($has_gone_one_day && $is_monday){
            $this->slot_counter = 0;
        }
    }

    private function setSlotTime()
    {
        $adj_delay = U::adjustInterval($this->delay, $this->slot_duration);
        $delay_count = U::divideDuration($adj_delay, $this->slot_duration);
        $value = ($this->slot_counter - $delay_count) * $this->slot_duration[0];
        $unit = $this->slot_duration[1];
        $this->slot_time = [$value, $unit];
    }

    private function checkIfRightTime($task_type)
    {
        $interval = $this->intervals[$task_type];
        $interval = U::adjustInterval($interval, $this->slot_duration);
        $mod = fmod(U::divideDuration($this->slot_time, $interval), 1.0);        
        return $mod === 0.0;
    }


}
