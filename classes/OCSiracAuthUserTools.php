<?php

class OCSiracAuthUserTools
{
    const REMOTED_PREFIX = 'sirac_';

    public static function generateUserRemoteId(OCSiracAuthUserHandlerInterface $handler)
    {
        $mappedVars = $handler->getMappedVars();
        if (!empty($mappedVars['FiscalCode'])) {
            return self::REMOTED_PREFIX . $mappedVars['FiscalCode'];
        }

        throw new Exception('Fiscal code not found');
    }

    public static function isSiracUser(eZUser $user)
    {
        $remoteId = $user->contentObject()->attribute('remote_id');

        return strpos($remoteId, self::REMOTED_PREFIX) !== false;
    }

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
                        //$remoteId = $handler->generateUserRemoteId();
                        //self::fixRemoteIdIfNeeded($userObject, $remoteId);
                    }
                }
            }
        }

        return $user;
    }

    public static function getUserByRemoteId(OCSiracAuthUserHandlerInterface $handler)
    {
        $user = false;

        $remoteId = $handler->generateUserRemoteId();
        $userObject = eZContentObject::fetchByRemoteID($remoteId);
        if ($userObject instanceof eZContentObject) {
            $user = eZUser::fetch($userObject->attribute('id'));
        }

        return $user;
    }


    public static function getUserByEmail(OCSiracAuthUserHandlerInterface $handler)
    {
        $mappedVars = $handler->getMappedVars();
        $user = eZUser::fetchByEmail($mappedVars['UserEmail']);

        //if ($user instanceof eZUser) {
        //    $remoteId = $handler->generateUserRemoteId();
        //    $object = $user->contentObject();
        //    self::fixRemoteIdIfNeeded($object, $remoteId);
        //
        //}

        return $user;
    }

    public static function fixRemoteIdIfNeeded(eZContentObject $object, $remoteId)
    {
        if ($object->attribute('remote_id') != $remoteId) {
            $object->setAttribute('remote_id', $remoteId);
            $object->store();
        }
    }

    public static function onInputPreventPasswordChange(eZURI $uri)
    {
        if (($uri->URI == 'user/password' || $uri->URI == 'userpaex/password/' . eZUser::currentUserID())
            && self::isSiracUser(eZUser::currentUser())) {

            $http = eZHTTPTool::instance();
            $redirectUrl = '/sirac/change_password';
            eZURI::transformURI($redirectUrl);
            $http->redirect($redirectUrl);

        } else {
            return null;
        }
    }
}