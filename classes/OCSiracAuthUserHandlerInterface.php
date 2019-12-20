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
     * @return string
     */
    public function generateUserRemoteId();

    /**
     * @return eZContentClass
     */
    public function getUserClass();

    public function log($level, $message, $context);
}