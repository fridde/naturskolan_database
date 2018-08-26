<?php


namespace Fridde\Error;


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
            echo '<h2>Internal error</h2><p>The site has encountered an internal error and could not respond to your request.</p><p>The admin has been informed. Try again later!</p>';
            $slacker = new Slacker(SETTINGS['slacker']);
            $slacker->send('Kritiskt fel på NDB: ' . $msg);
        }
        //if($severity === )


        $this->Logger->addInfo($msg, ['source' => '']);

        if(defined('DEBUG') && !empty(DEBUG)){
            (new BlueScreen())->render($this->Exception);
        }
    }

    protected function createMessage(): string
    {
        $msg = $this->Exception->getMessage();

        if($this->Exception instanceof NException){
            $code = $this->Exception->getCode();
            $template = Error::getTemplate($code);
            $args = $this->Exception->getInfo();
            $msg .= sprintf($template, ...$args);
        }
        $msg .= ' The error occurred at ';
        $msg .= $this->Exception->getFile().':'.$this->Exception->getLine();

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
