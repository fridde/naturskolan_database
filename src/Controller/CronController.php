<?php

namespace Fridde\Controller;

use Fridde\Annotations\SecurityLevel;
use Fridde\Error\Error;
use Fridde\Error\NException;
use Fridde\Essentials;
use Fridde\Security\Authorizer;
use Fridde\Timing as T;
use Fridde\Task;

class CronController extends BaseController
{
    private $intervals;

    public function __construct(array $params = [])
    {
        parent::__construct($params, true);
        $this->setReturnType(self::RETURN_JSON);
        $this->intervals = SETTINGS['cronjobs']['intervals'];
    }

    public function handleRequest(): ?string
    {
        if($this->hasAction('executeTaskNow')){
            $this->executeTaskNow();
            return ;
        }

        $this->addAction('run');
        return parent::handleRequest();
    }

    /**
     * @SecurityLevel(SecurityLevel::ACCESS_ALL)
     */
    public function run(): void
    {
        $this->checkIfAuthorized();

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
    public function executeTaskNow(): void
    {
        $task = new Task($this->getParameter('type'));
        if(! $task->isExempted()){
            throw new NException(Error::UNAUTHORIZED_ACTION, [$task]);
        }

        $success = $task->execute();
        if ($success) {
            $this->N->setLastRun($task->getType());
        }

    }

    private function checkIfRightTime(string $task_type): bool
    {
        $last_completion = $this->N->getLastRun($task_type);
        if (empty($last_completion)) {
            return true;
        }

        return T::longerThanSince($this->intervals[$task_type], $last_completion);
    }

    private function checkIfAuthorized(): bool
    {
        $env = defined('ENVIRONMENT') ? ENVIRONMENT : null;

        if(in_array($env, [Essentials::ENV_TEST, Essentials::ENV_DEV], true)){
            return true;
        }
        $code = $this->getParameter('code');
        if(empty($code)){
            return false;
        }
        $hash = $this->N->getStatus('cron.auth_key_hash');
        if(password_verify($code, $hash)){
            return true;
        }

        throw new \Exception('A client tried to run cron on production without giving a valid code');

    }

}
