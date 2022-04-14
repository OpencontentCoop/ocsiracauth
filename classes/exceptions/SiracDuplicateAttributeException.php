<?php

class SiracDuplicateAttributeException extends SiracException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct('Duplicate attribute: ' . $message, $code, $previous);
    }

    public function getErrorCode()
    {
        return self::DUPLICATE_VALUE_ERROR;
    }
}