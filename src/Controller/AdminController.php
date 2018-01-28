<?php


namespace Fridde\Controller;

use Fridde\Entities\Group;
use Fridde\Task;

class AdminController extends BaseController
{
    public function handleRequest()
    {
        $task_keys = array_keys($this->N::getSetting('cronjobs', 'intervals'));
        $task_status = $this->N->getCronTaskActivationStatus();
        $tasks = [];
        foreach($task_keys as $task_key){
            $task = ['value' => $task_key];
            $task['label'] = implode(' ', array_map('ucfirst', explode('_', $task_key)));
            $task['status'] = $task_status[$task_key] ?? 0;
            $tasks[] = $task;
        }
        $this->addToDATA('tasks', $tasks);
        $this->addToDATA('grades', Group::getGradeLabels());
        $this->addToDATA('school_id', 'natu');
        $this->setTemplate('admin_area');
        parent::handleRequest();
    }
}