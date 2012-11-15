<?php

/**
 * This is a functioning PHP script to do a simple call to the trending
 * endpoint for the Untappd API.
 *
 */


set_include_path(get_include_path() . PATH_SEPARATOR . '../library/');

require_once 'Pintlabs/Service/Untappd.php';

$config = array(
    'clientId'     => '',
    'clientSecret' => '',
    'redirectUri'  => '',
    'accessToken'  => '',
);

$untappd = new Pintlabs_Service_Untappd($config);

try {
    $feed = $untappd->publicTrending();
} catch (Exception $e) {
    die($e->getMessage());
}

foreach ($feed->response->micro->items as $i) {
    echo $i->beer->beer_name . ' (' . $i->total_count . ')<br />';
}
