<?php

/**
 * This is a functioning PHP script to redirect your user to Untappd to allow
 * them to authorize your app to use their credentials.
 *
 */


set_include_path(get_include_path() . PATH_SEPARATOR . '../../library/');

require_once 'Pintlabs/Service/Untappd.php';

$config = array(
    'clientId'     => '--REQUIRED--',
    'clientSecret' => '--REQUIRED--',
    'redirectUri'  => '--REQUIRED--',
    'accessToken'  => '',
);

$untappd = new Pintlabs_Service_Untappd($config);

$uri = $untappd->authenticateUri();

// This will redirect them to the correct place
header('Location: ' . $uri);