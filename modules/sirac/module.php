<?php

$Module = array('name' => 'Sirac Auth');

$ViewList = array();
$ViewList['auth'] = array(
    'functions' => array('auth'),
    'script' => 'auth.php',
    'params' => array(),
    'unordered_params' => array()
);
$ViewList['logout'] = array(
    'functions' => array('auth'),
    'script' => 'logout.php',
    'params' => array(),
    'unordered_params' => array()
);
$ViewList['change_password'] = array(
    'functions' => array('auth'),
    'script' => 'change_password.php',
    'params' => array(),
    'unordered_params' => array()
);


$FunctionList = array();
$FunctionList['auth'] = array();


