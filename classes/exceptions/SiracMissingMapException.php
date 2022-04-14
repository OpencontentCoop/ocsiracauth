<?php

class SiracMissingMapException extends SiracException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct('Missing or wrong attribute map configuration: ' . $message, $code, $previous);
    }

    public function getErrorCode()
    {
        return self::MISSING_MAP_ERROR;
    }
}