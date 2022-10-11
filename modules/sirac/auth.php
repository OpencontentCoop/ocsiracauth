<?php
/** @var array $Params */
/** @var eZModule $module */
$module = $Params['Module'];
$ini = eZINI::instance('ocsiracauth.ini');

$handlerClass = $ini->variable('HandlerSettings', 'UserHandler');
if (class_exists($handlerClass)) {
    /** @var OCSiracAuthUserHandlerInterface|OCSiracReloadableHandlerInterface $handler */
    $handler = new $handlerClass();
} else {
    eZDebug::writeError("Missing ini configuration ocsiracauth.ini[HandlerSettings]UserHandler", __FILE__);
    return $module->handleError(SiracException::CONFIGURATION_ERROR, 'sirac');
}

if ($Params['EmbedOauth'] === '_oauth' && OCSiracEmbedOauth::instance()->supports($handler)) {
    try {
        $oauthRunnerResult = OCSiracEmbedOauth::instance()->run($module, $handler);
        if ($oauthRunnerResult === OCSiracEmbedOauth::ALREADY_LOGGED) {
            return;
        }
    } catch (Exception $e) {
        eZDebug::writeError($e->getMessage(), __FILE__);
        return $module->handleError(SiracException::OEMBED_ERROR, 'sirac');
    }
}

try {
    if (isset($_GET['inspect'])) {
        echo '<strong><code>Varibili recuperate dal sistema di autenticazione</code></strong>';
        echo '<pre>';print_r($handler->getServerVars());echo '</pre>';
        echo '<strong><code>Variabili mappate</code></strong>';
        echo '<pre>';print_r($handler->getMappedVars());echo '</pre>';
        echo '<strong><code>Configurazione del gestore delle variabili</code></strong>';
        echo '<pre>';print_r($ini->group('HandlerSettings'));echo '</pre>';
        echo '<strong><code>Mappa delle variabili</code></strong>';
        echo '<pre>';print_r($ini->group('Mapper'));echo '</pre>';
        eZDisplayDebug();
        eZExecution::cleanExit();
    }
    return $handler->login($module);

} catch (SiracDuplicateEmailException $e) {
    if ($e->getUser() instanceof eZUser) {
        $tpl = eZTemplate::factory();
        $tpl->setVariable('login_attributes', $e->getLoginAttributes());
        $tpl->setVariable('user', $e->getUser());
        OCSiracEmailVerifier::instanceFromUser($e->getUser())->sendMail($e->getLoginAttributes());
        $Result['content'] = $tpl->fetch('design:sirac/verify_email_form.tpl');
        $Result['node_id'] = 0;
    }else{
        return $module->handleError($e->getErrorCode(), 'sirac');
    }

} catch (SiracException $e) {
    eZDebug::writeError($e->getMessage(), __FILE__);
    return $module->handleError(SiracException::DUPLICATE_VALUE_ERROR, 'sirac');

} catch (Exception $e) {
    eZDebug::writeError($e->getMessage(), __FILE__);
    return $module->handleError(SiracException::UNKNOWN_ERROR, 'sirac');
}
