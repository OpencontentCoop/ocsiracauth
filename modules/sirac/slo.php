<?php

/** @var eZModule $module */
$module = $Params['Module'];

eZSession::remove();

$redirectUrl = isset($_REQUEST["return"]) ? $_REQUEST["return"] : '/';
$module->RedirectURI = $redirectUrl;
$module->setExitStatus(eZModule::STATUS_REDIRECT);
return;