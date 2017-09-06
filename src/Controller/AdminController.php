<?php


namespace Fridde\Controller;

use Fridde\Entities\Group;
use Fridde\Task;

class AdminController extends BaseController
{
    public function handleRequest()
    {
        $task_keys = array_keys(Task::TASK_TO_METHOD_MAP);
        $task_status = $this->N->getCronTasks();
        $tasks = [];
        foreach($task_keys as $task_key){
            $task = ["value" => $task_key];
            $task["label"] = implode(' ', array_map('ucfirst', explode('_', $task_key)));
            $task["status"] = $task_status[$task_key] ?? 0;
            $tasks[] = $task;
        }
        $options['DATA']['tasks'] = $tasks;
        $options['DATA']['grades'] = Group::getGradeLabels();
        $options["template"] = "admin_area";
        $this->standardRender($options);
    }
}