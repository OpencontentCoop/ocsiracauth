<?php

$Module = array('name' => 'Sirac Verify');

$ViewList = array();

$ViewList['verify_email'] = array(
    'functions' => array('auth'),
    'script' => 'verify_email.php',
    'params' => array(),
    'unordered_params' => array()
);

$FunctionList = array();
$FunctionList['verify_email'] = array();


