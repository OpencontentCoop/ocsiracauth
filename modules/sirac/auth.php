<?php
/** @var array $Params */
/** @var eZModule $module */
$module = $Params['Module'];
$ini = eZINI::instance('ocsiracauth.ini');

$handlerClass = $ini->variable('HandlerSettings', 'UserHandler');
if (class_exists($handlerClass)) {
    /** @var OCSiracAuthUserHandlerInterface $handler */
    $handler = new $handlerClass();
} else {
    eZDebug::writeError("Missing ini configuration ocsiracauth.ini[HandlerSettings]UserHandler");
    return $module->handleError(eZError::KERNEL_NOT_AVAILABLE, 'kernel', array(), array('OCSiracAuthError', 2));
}

try {

    if (isset($_GET['inspect'])) {
        echo '<pre>';
        print_r($handler->getServerVars());
        print_r($handler->getMappedVars());
        print_r($ini->group('HandlerSettings'));
        eZDisplayDebug();
        eZExecution::cleanExit();
    }

    return $handler->login($module);

} catch (Exception $e) {

    eZDebug::writeError($e->getMessage());

    return $module->handleError(eZError::KERNEL_NOT_FOUND, 'kernel', array(), array('OCSiracAuthError', 2));
}