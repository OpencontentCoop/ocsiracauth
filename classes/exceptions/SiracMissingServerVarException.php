<?php

class SiracMissingServerVarException extends SiracException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct('Missing required server var: ' . $message, $code, $previous);
    }

    public function getErrorCode()
    {
        return self::MISSING_SERVER_VAR_ERROR;
    }
}