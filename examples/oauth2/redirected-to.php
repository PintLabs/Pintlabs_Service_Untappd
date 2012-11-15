<?php

/**
 * This is a functioning PHP script to process the redirect that comes back
 * from Untappd once a user has authorized your app to use their account.  There
 * will be a "code" argument in the query string that you will need to process.
 *
 */


set_include_path(get_include_path() . PATH_SEPARATOR . '../library/');

require_once 'Pintlabs/Service/Untappd.php';

$config = array(
    'clientId'     => '--REQUIRED--',
    'clientSecret' => '--REQUIRED--',
    'redirectUri'  => '--REQUIRED--',
    'accessToken'  => '',
);

$untappd = new Pintlabs_Service_Untappd($config);

/**
 * The getAccessToken() method automatically assigns the asscess token within
 * the Pintlabs_Service_Untappd class.  If you instantiate the class later, you
 * will need to pass the stored access token to the constructor in the config
 * array.
 */
$accessToken = $untappd->getAccessToken($_GET['code']);

$authorizedUser = $untappd->userInfo();

/**
 * Store the $accessToken in your db for user with the user who authorized you,
 * which you know by calling the userInfo() method with no params.
 */
