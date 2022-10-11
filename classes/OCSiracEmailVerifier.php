<?php

class OCSiracEmailVerifier
{
    private $user;

    public static function instanceFromUser(eZUser $user)
    {
        $i = new OCSiracEmailVerifier();
        $i->setUser($user);
        return $i;
    }

    public static function instanceFromHash($hash)
    {
        $siteData = eZSiteData::fetchObject(eZSiteData::definition(), null, ['value' => $hash]);
        if ($siteData instanceof eZSiteData) {
            $userId = str_replace('svu_hash_', '', $siteData->attribute('name'));
            $user = eZUser::fetch((int)$userId);
            if ($user instanceof eZUser) {
                return self::instanceFromUser($user);
            }
        }

        return new OCSiracEmailVerifier();
    }

    /**
     * @return eZUser
     */
    public function mergeUser()
    {
        $siteData = eZSiteData::fetchByName('svu_attributes_' . $this->getUser()->id());
        if ($siteData instanceof eZSiteData && $this->getUser() instanceof eZUser) {
            $attributes = json_decode($siteData->attribute('value'), true);
            $user = $this->getUser();
            if ($user->attribute('login') !== $attributes['mapped_vars']['UserLogin']) {
                $user->setAttribute('login', $attributes['mapped_vars']['UserLogin']);
                $user->store();
            }
            $userObject = $user->contentObject();
            if (!empty($attributes['remote_id']) && $attributes['remote_id'] !== $userObject->attribute('remote_id')) {
                $remoteId = $attributes['remote_id'];
                $remoteIdAlreadyExists = eZContentObject::fetchByRemoteID($remoteId);
                if (!$remoteIdAlreadyExists) {
                    $userObject->setAttribute('remote_id', $remoteId);
                    $userObject->store();
                    eZContentObject::clearCache($userObject->attribute('id'));
                    $userObject = eZContentObject::fetchByRemoteID($remoteId);
                }
            }
            eZContentFunctions::updateAndPublishObject($userObject, ['attributes' => $attributes['attributes']]);
            $this->deleteHashAndAttributes();

            return $user;
        }

        return $this->getUser();
    }

    private function deleteHashAndAttributes()
    {
        if ($this->getUser() instanceof eZUser) {
            $siteDataName = 'svu_hash_' . $this->getUser()->id();
            eZPersistentObject::removeObject(
                eZSiteData::definition(),
                ['name' => $siteDataName]
            );
            $siteDataName = 'svu_attributes_' . $this->getUser()->id();
            eZPersistentObject::removeObject(
                eZSiteData::definition(),
                ['name' => $siteDataName]
            );
        }
    }

    private function generateHash()
    {
        $hash = bin2hex(openssl_random_pseudo_bytes(16));
        return $hash;
    }

    public function sendMail($loginAttributes = [])
    {
        if (!$this->getUser() instanceof eZUser) {
            return false;
        }

        eZDB::instance()->begin();
        $hash = $this->generateHash();
        $this->deleteHashAndAttributes();
        eZSiteData::create('svu_hash_' . $this->getUser()->id(), $hash)->store();
        eZSiteData::create('svu_attributes_' . $this->getUser()->id(), json_encode($loginAttributes))->store();
        eZDB::instance()->commit();

        $ini = eZINI::instance();
        $tpl = eZTemplate::factory();
        $tpl->setVariable('hash', $hash);
        $tpl->setVariable('user', $this->getUser());
        $body = $tpl->fetch('design:sirac/verify_email_code_mail.tpl');

        if ($tpl->hasVariable('subject')) {
            $subject = $tpl->variable('subject');
        } else {
            $subject = ezpI18n::tr('ocsirac/verify_email', "Verify your account");
        }

        $emailSender = $ini->variable('MailSettings', 'EmailSender');
        if (!$emailSender) {
            $emailSender = $ini->variable('MailSettings', 'AdminEmail');
        }

        $tpl->setVariable('title', $subject);
        $tpl->setVariable('content', $body);
        $templateResult = $tpl->fetch('design:mail/sensor_mail_pagelayout.tpl');

        $mail = new eZMail();
        $mail->setSender($emailSender);
        $receiver = $this->getUser()->attribute('email');
        $mail->setReceiver($receiver);
        $mail->setSubject($subject);
        $mail->setBody($templateResult);
        $mail->setContentType('text/html');
        return eZMailTransport::send($mail);
    }

    private function generateCode(eZUser $user, $attributes = [])
    {
    }

    /**
     * @return eZUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param eZUser $user
     */
    public function setUser(eZUser $user): void
    {
        $this->user = $user;
    }
}