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
        $this->addAction('run');
        if ($this->authorizeViaAuthkey($this->getParameter('AuthKey'))) {
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

    private function checkIfRightTime(string $task_type)
    {
        $last_completion = $this->N->getLastRun($task_type);
        if (empty($last_completion)) {
            return true;
        }

        return T::longerThanSince($this->intervals[$task_type], $last_completion);
    }

    private function authorizeViaAuthkey(string $key = null): bool
    {
        $hash = $this->N->getStatus('cron.auth_key_hash');

        return password_verify($key, $hash);
    }


}
