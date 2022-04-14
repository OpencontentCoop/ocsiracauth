<?php

class SiracException extends Exception
{
    const UNKNOWN_ERROR = 1;

    const CONFIGURATION_ERROR = 2;

    const OEMBED_ERROR = 3;

    const DUPLICATE_VALUE_ERROR = 10;

    const MISSING_MAP_ERROR = 20;

    const MISSING_SERVER_VAR_ERROR = 30;

    public function getErrorCode()
    {
        return self::UNKNOWN_ERROR;
    }
}