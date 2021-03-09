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
                    $userObject = self::fetchObjectByFiscalCode($fiscalCode, $attribute->attribute('id'));
                    if ($userObject instanceof eZContentObject){
                        $user = eZUser::fetch($userObject->attribute('id'));
                    }
                }
            }
        }

        return $user;
    }

    private static function fetchObjectByFiscalCode($fiscalCode, $contentClassAttributeID)
    {
        $query = "SELECT co.id
				FROM ezcontentobject co, ezcontentobject_attribute coa
				WHERE co.id = coa.contentobject_id
				AND co.current_version = coa.version								
				AND coa.contentclassattribute_id = " . intval($contentClassAttributeID) . "
				AND UPPER(coa.data_text) = '" . eZDB::instance()->escapeString(strtoupper($fiscalCode)) . "'";

        $result = eZDB::instance()->arrayQuery($query);
        if (isset($result[0]['id'])){
            return eZContentObject::fetch((int)$result[0]['id']);
        }

        return false;
    }

    public static function getUserByEmail(OCSiracAuthUserHandlerInterface $handler)
    {
        $mappedVars = $handler->getMappedVars();
        $user = eZUser::fetchByEmail($mappedVars['UserEmail']);

        return $user;
    }

}