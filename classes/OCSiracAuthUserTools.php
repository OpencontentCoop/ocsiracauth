<?php

class OCSiracAuthUserTools
{
    public static function getUserByFiscalCode(OCSiracAuthUserHandlerInterface $handler)
    {
        $mappedVars = $handler->getMappedVars();
        if (empty($mappedVars['FiscalCode'])) {
            return false;
        }
        $fiscalCode = $mappedVars['FiscalCode'];

        if (class_exists('OCCodiceFiscaleType')) {
            $classes = self::getUserClasses($handler);
            foreach ($classes as $class) {
                /** @var eZContentClassAttribute $attribute */
                foreach ($class->dataMap() as $attribute) {
                    if ($attribute->attribute('data_type_string') == OCCodiceFiscaleType::DATA_TYPE_STRING) {
                        $userObject = self::fetchObjectByFiscalCode($fiscalCode, $attribute->attribute('id'));
                        if ($userObject instanceof eZContentObject) {
                            $user = eZUser::fetch($userObject->attribute('id'));
                            if ($user instanceof eZUser){
                                return $user;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    public static function getUserByFiscalCodeAndEmail(OCSiracAuthUserHandlerInterface $handler)
    {
        $mappedVars = $handler->getMappedVars();
        if (empty($mappedVars['FiscalCode'])) {
            return false;
        }
        $fiscalCode = $mappedVars['FiscalCode'];
        $email = $mappedVars['UserEmail'];

        if (class_exists('OCCodiceFiscaleType')) {
            $classes = self::getUserClasses($handler);
            foreach ($classes as $class) {
                /** @var eZContentClassAttribute $attribute */
                foreach ($class->dataMap() as $attribute) {
                    if ($attribute->attribute('data_type_string') == OCCodiceFiscaleType::DATA_TYPE_STRING) {
                        $userObject = self::fetchObjectByFiscalCode($fiscalCode, $attribute->attribute('id'));
                        if ($userObject instanceof eZContentObject) {
                            $user = eZUser::fetch($userObject->attribute('id'));
                            if ($user instanceof eZUser && strtolower($user->Email) == strtolower($email) ){
                                return $user;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    private static function getUserClasses(OCSiracAuthUserHandlerInterface $handler)
    {
        $classes = [$handler->getUserClass()];
        if ($handler instanceof OCSiracAuthUserHandlerMultiClassesCapableInterface) {
            $classes = $handler->getExistingUserClasses();
        }

        return $classes;
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
        if (isset($result[0]['id'])) {
            return eZContentObject::fetch((int)$result[0]['id']);
        }

        return false;
    }

    public static function getUserByEmail(OCSiracAuthUserHandlerInterface $handler)
    {
        $mappedVars = $handler->getMappedVars();

        /** @var eZUser[] $users */
        $users = eZPersistentObject::fetchObjectList(
            eZUser::definition(),
            null,
            ['LOWER( email )' => strtolower($mappedVars['UserEmail'])]
        );

        if (count($users) === 0) {
            return false;
        }

        if (count($users) === 1) {
            return $users[0];
        }

        $usersByClass = [];
        foreach ($users as $user) {
            if ($user->contentObject() instanceof eZContentObject) {
                $usersByClass[$user->contentObject()->attribute('class_identifier')][] = $user;
            }
        }
        if (count($usersByClass) > 0) {
            foreach (self::getUserClasses($handler) as $userClass) {
                if (isset($usersByClass[$userClass->attribute('identifier')])) {
                    return $usersByClass[$userClass->attribute('identifier')][0];
                }
            }
        }

        return false;
    }

    public static function getUserByLogin(OCSiracAuthUserHandlerInterface $handler)
    {
        $mappedVars = $handler->getMappedVars();
        $user = eZUser::fetchByName($mappedVars['UserLogin']);

        return $user;
    }

    public static function getUserByRemoteId(OCSiracAuthUserHandlerInterface $handler)
    {
        $remoteId = false;
        $remoteGenerator = eZINI::instance('ocsiracauth.ini')->variable('HandlerSettings', 'RemoteIdGenerator');
        if (is_callable($remoteGenerator)) {
            $remoteId = call_user_func($remoteGenerator, $handler);
        }

        if ($remoteId) {
            $remoteIdAlreadyExists = eZContentObject::fetchByRemoteID($remoteId);
            if ($remoteIdAlreadyExists instanceof eZContentObject) {
                $classes = self::getUserClasses($handler);
                foreach ($classes as $class) {
                    if ($remoteIdAlreadyExists->attribute('class_identifier') == $class->attribute('identifier')) {
                        return eZUser::fetch($remoteIdAlreadyExists->attribute('id'));
                    }
                }
            }
        }

        return null;
    }

    public static function generateUserRemoteId(OCSiracAuthUserHandlerInterface $handler)
    {
        return eZRemoteIdUtility::generate('object');
    }

}