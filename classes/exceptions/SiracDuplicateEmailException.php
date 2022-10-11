<?php

class SiracDuplicateEmailException extends SiracDuplicateAttributeException
{
    /**
     * @var eZUser
     */
    private $user;

    /**
     * @var array
     */
    private $loginAttributes = [];

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct('UserEmail');
    }

    /**
     * @return eZUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param eZUser $userData
     */
    public function setUser(eZUser $userData)
    {
        $this->user = $userData;
    }

    /**
     * @return array
     */
    public function getLoginAttributes(): array
    {
        return $this->loginAttributes;
    }

    /**
     * @param array $loginAttributes
     */
    public function setLoginAttributes(array $loginAttributes): void
    {
        $this->loginAttributes = $loginAttributes;
    }

}