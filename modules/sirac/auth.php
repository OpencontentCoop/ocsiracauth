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
    return $module->handleError(eZError::KERNEL_NOT_AVAILABLE, 'kernel', [], ['OCSiracAuthError', 2]);
}

if ($Params['EmbedOauth'] === '_oauth' && OCSiracEmbedOauth::instance()->supports($handler)) {
    try {
        $oauthRunnerResult = OCSiracEmbedOauth::instance()->run($module, $handler);
        if ($oauthRunnerResult === OCSiracEmbedOauth::ALREADY_LOGGED) {
            return;
        }
    } catch (Exception $e) {
        eZDebug::writeError($e->getMessage(), __FILE__);
        return $module->handleError(eZError::KERNEL_NOT_FOUND, 'kernel', [], ['OCSiracAuthError', 3]);
    }
}

try {

    if (isset($_GET['inspect'])) {
        echo '<pre>';
        print_r($handler->getServerVars());
        print_r($handler->getMappedVars());
        print_r($ini->group('HandlerSettings'));
        print_r($ini->group('Mapper'));
        eZDisplayDebug();
        eZExecution::cleanExit();
    }

    return $handler->login($module);

} catch (Exception $e) {

    eZDebug::writeError($e->getMessage(), __FILE__);

    return $module->handleError(eZError::KERNEL_NOT_FOUND, 'kernel', [], ['OCSiracAuthError', 2]);
}
