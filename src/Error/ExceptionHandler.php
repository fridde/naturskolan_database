<?php


namespace Fridde\Error;


use Fridde\Controller\ErrorController;
use Fridde\Slacker;
use Monolog\Logger;
use Tracy\BlueScreen;

class ExceptionHandler
{

    private $Exception;
    private $Logger;

    /**
     * ExceptionHandler constructor.
     */
    public function __construct(\Exception $exception, Logger $logger)
    {
        $this->Exception = $exception;
        $this->Logger = $logger;
    }


    public function handle()
    {
        $code  = $this->Exception->getCode();
        $severity = Error::getSeverity($code);
        $msg = $this->createMessage();

        if($severity === Error::SEVERITY_FATAL){
            header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);
            echo $msg;
            $slacker = new Slacker(SETTINGS['slacker']);
            $slacker->sendToAdmin('Kritiskt fel på NDB: ' . $msg);
        }
        if($severity === Error::SEVERITY_BAD_DATA){
            $slacker = new Slacker(SETTINGS['slacker']);
            $slacker->sendToAdmin('För kännedom: ' . $msg);
        }
        if($severity === Error::SEVERITY_USER_INTERACTION){
            $params = ['action' => 'displayErrorMessage'];
            $params['message'] = $msg;
            $error_controller = new ErrorController($params);

            $error_controller->handleRequest();

        }


        $this->Logger->addInfo($msg, ['source' => '']);

        if(defined('DEBUG') && !empty(DEBUG)){
            (new BlueScreen())->render($this->Exception);
        }
    }

    protected function createMessage(): string
    {
        $msg = '';

        $url = $_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? null);
        if(!empty($url)){
            $msg .= 'Request for ' . $url . PHP_EOL;
        }

        $msg .= $this->Exception->getMessage();

        if($this->Exception instanceof NException){
            $code = $this->Exception->getCode();
            $template = Error::getTemplate($code);
            $args = array_values($this->Exception->getInfo());
            $msg .= sprintf($template, ...$args);
        }
        $msg .= ' The error occurred at ';
        $msg .= $this->Exception->getFile().':'.$this->Exception->getLine();
        $msg .= '; Stacktrace: ' . $this->Exception->getTraceAsString();

        return $msg;
    }



}

/*
 *     header($_SERVER['SERVER_PROTOCOL'].' 500 Internal Server Error', true, 500);

header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);
echo 'No match found. Requested url: '.PHP_EOL;
echo $request_url;
exit();
 */
