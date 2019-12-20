<?php

interface OCSiracAuthUserHandlerInterface
{
    public function login(eZModule $module);

    public function logout(eZModule $module);

    /**
     * @return array
     */
    public function getServerVars();

    /**
     * @return array
     */
    public function getMappedVars();

    /**
     * @return eZContentClass
     */
    public function getUserClass();

    public function log($level, $message, $context);
}