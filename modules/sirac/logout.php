<?php
/** @var eZModule $module */
$module = $Params['Module'];
$ini = eZINI::instance('ocsiracauth.ini');

$handlerClass = $ini->variable('HandlerSettings', 'UserHandler');
if (class_exists($handlerClass)){
	$handler = new $handlerClass();
}else{
	eZDebug::writeError("Missing ini configuration ocsiracauth.ini[HandlerSettings]UserHandler");
	$module->redirectTo('/');
    return;
}

try{
	return $handler->logout($module);	
}catch(Exception $e){
	eZDebug::writeError($e->getMessage());	
}
