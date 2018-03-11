<?php

namespace Fridde\Controller;

use Fridde\Timing as T;
use Fridde\Task;

class CronController extends BaseController
{
    private $intervals;

    public function __construct(array $params)
    {
        parent::__construct($params);
        $cron_settings = SETTINGS['cronjobs'];
        $this->intervals = $cron_settings['intervals'];
    }

    public function run()
    {
        $active_tasks = array_filter($this->N->getCronTaskActivationStatus());
        foreach (array_keys($active_tasks) as $task_type) {
            if ($this->checkIfRightTime($task_type)) {
                $task = new Task($task_type);
                $success = $task->execute();
                if($success){
                    $this->N->setLastRun($task_type);
                }
            }
        }
    }

    public function executeTask()
    {
        $task_type = $this->params['type'];
        $task = new Task($task_type);
        $task->execute(); // ignores task activation in SystemStatus
    }

    private function checkIfRightTime(string $task_type)
    {
        $last_completion = $this->N->getLastRun($task_type);
        if(empty($last_completion)){
            return true;
        }
        return T::longerThanSince($this->intervals[$task_type], $last_completion);
    }


}
