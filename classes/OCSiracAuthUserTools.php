<?php

class OCSiracAuthUserTools
{
    public static function getUserByFiscalCode(OCSiracAuthUserHandlerInterface $handler)
    {
        $user = false;

        $mappedVars = $handler->getMappedVars();
        if (empty($mappedVars['FiscalCode'])) {
            throw new Exception('Fiscal code not found');
        }
        $fiscalCode = $mappedVars['FiscalCode'];

        if (class_exists('OCCodiceFiscaleType')) {
            /** @var eZContentClassAttribute $attribute */
            foreach ($handler->getUserClass()->dataMap() as $attribute){
                if ($attribute->attribute('data_type_string') == OCCodiceFiscaleType::DATA_TYPE_STRING){
                    $userObject = OCCodiceFiscaleType::fetchObjectByCodiceFiscale($fiscalCode, $attribute->attribute('id'));
                    if ($userObject instanceof eZContentObject){
                        $user = eZUser::fetch($userObject->attribute('id'));
                    }
                }
            }
        }

        return $user;
    }

    public static function getUserByEmail(OCSiracAuthUserHandlerInterface $handler)
    {
        $mappedVars = $handler->getMappedVars();
        $user = eZUser::fetchByEmail($mappedVars['UserEmail']);

        return $user;
    }

}