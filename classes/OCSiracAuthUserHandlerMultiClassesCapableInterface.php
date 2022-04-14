<?php

interface OCSiracAuthUserHandlerMultiClassesCapableInterface
{
    /**
     * @return eZContentClass[]
     */
    public function getExistingUserClasses();
}