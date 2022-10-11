<?php

$Module = array('name' => 'Sirac Auth');

$ViewList = array();
$ViewList['auth'] = array(
    'functions' => array('auth'),
    'script' => 'auth.php',
    'params' => array('EmbedOauth'),
    'unordered_params' => array()
);
$ViewList['logout'] = array(
    'functions' => array('auth'),
    'script' => 'logout.php',
    'params' => array(),
    'unordered_params' => array()
);
$ViewList['slo'] = array(
    'functions' => array('auth'),
    'script' => 'slo.php',
    'params' => array(),
    'unordered_params' => array()
);
$ViewList['verify_email'] = array(
    'functions' => array('auth'),
    'script' => 'verify_email.php',
    'params' => array(),
    'unordered_params' => array()
);

$FunctionList = array();
$FunctionList['auth'] = array();


