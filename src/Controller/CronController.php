<?php

namespace Fridde\Controller;

use Fridde\Security\Authorizer;
use Fridde\Timing as T;
use Fridde\Task;

class CronController extends BaseController
{
    private $intervals;

    protected $Security_Levels = [
        'run' => Authorizer::ACCESS_ADMIN_ONLY,
    ];

    public function __construct(array $params)
    {
        parent::__construct($params, true);
        $this->intervals = SETTINGS['cronjobs']['intervals'];
    }

    public function handleRequest()
    {
        if($this->hasAction('executeTaskNow')){
            $this->executeTaskNow();
            return ;
        }

        $this->addAction('run');
        if ($this->isAuthorizedViaAuthkey()) {
            $this->Security_Levels['run'] = Authorizer::ACCESS_ALL;
        }
        parent::handleRequest();
    }

    public function run()
    {
        $active_tasks = array_filter($this->N->getCronTaskActivationStatus());
        foreach (array_keys($active_tasks) as $task_type) {
            if ($this->checkIfRightTime($task_type)) {
                $task = new Task($task_type);
                $success = $task->execute();
                if ($success) {
                    $this->N->setLastRun($task_type);
                }
            }
        }
    }

    // careful, this task is executed no matter the status of the system
    public function executeTaskNow()
    {
        $task = new Task($this->getParameter('type'));
        if(!($task->isExempted() || $this->isAuthorizedViaAuthkey())){
            throw new \Exception('Tried to execute task with bad/wrong AuthKey');
        }

        $success = $task->execute();
        if ($success) {
            $this->N->setLastRun($task->getType());
        }

    }

    private function checkIfRightTime(string $task_type)
    {
        $last_completion = $this->N->getLastRun($task_type);
        if (empty($last_completion)) {
            return true;
        }

        return T::longerThanSince($this->intervals[$task_type], $last_completion);
    }

    private function isAuthorizedViaAuthkey(string $key = null): bool
    {
        $key = $key ?? $this->getParameter('AuthKey');
        $hash = $this->N->getStatus('cron.auth_key_hash');

        return password_verify($key, $hash);
    }


}
