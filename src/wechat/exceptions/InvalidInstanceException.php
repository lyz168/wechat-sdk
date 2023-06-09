<?php

namespace lyz\wechat\exceptions;

/**
 * 加载类异常
 * Class InvalidInstanceException
 * @package lyz\wechat\exceptions
 */
class InvalidInstanceException extends \Exception
{
    /**
     * @var array
     */
    public $raw = [];

    /**
     * InvalidInstanceException constructor.
     * @param string $message
     * @param integer $code
     * @param array $raw
     */
    public function __construct($message, $code = 0, $raw = [])
    {
        parent::__construct($message, intval($code));
        $this->raw = $raw;
    }
}
