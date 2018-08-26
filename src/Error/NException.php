<?php


namespace Fridde\Error;


class NException extends \Exception
{
    protected $Info;

    public function __construct(int $code, array $info = [])
    {
        $this->setInfo($info);
        parent::__construct('', $code);
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return $this->Info;
    }

    /**
     * @param array $info
     */
    public function setInfo(array $Info): void
    {
        $this->Info = $Info;
    }
}
