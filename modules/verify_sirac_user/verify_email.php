<?php

/** @var eZModule $module */
$module = $Params['Module'];
$http = eZHTTPTool::instance();

if ($http->hasVariable('VerifyCode')) {
    $hash = trim($http->variable('VerifyCode'));

    $verifier = OCSiracEmailVerifier::instanceFromHash($hash);
    if ($verifier->getUser() instanceof eZUser) {
        $user = $verifier->mergeUser();
        $canLogin = eZUser::isEnabledAfterFailedLogin($user->id());
        $isEnabled = eZUserSetting::fetch($user->id())->attribute("is_enabled");
        if ($canLogin && $isEnabled) {
            $userID = $user->id();
            eZAudit::writeAudit('user-login', ['User id' => $userID, 'User login' => $user->attribute('login')]);
            eZUser::updateLastVisit($userID, true);
            eZUser::setCurrentlyLoggedInUser($user, $userID);
            eZUser::setFailedLoginAttempts($userID, 0);
            $module->redirectTo('/');
            return;
        }
    }
}

return $module->handleError(eZError::KERNEL_NOT_FOUND, 'kernel');

//create VerifyCode
//send mail
