<?php

/** @var eZModule $module */
$module = $Params['Module'];

eZSession::remove();

if (isset($_REQUEST["return"])){
    eZHTTPTool::headerVariable( 'Location', $_REQUEST["return"] );
    eZExecution::cleanExit();
}

$module->RedirectURI = '/';
$module->setExitStatus(eZModule::STATUS_REDIRECT);
return;