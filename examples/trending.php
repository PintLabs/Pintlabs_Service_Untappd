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

$lng = '18.058047';
$lat = '59.330207';
$radius = '6';

try {
    $feed = $untappd->publicFeed('', '', $lng, $lat, $radius);
} catch (Exception $e) {
    die($e->getMessage());
}

foreach ($feed->response->checkins->items as $i) {
    echo $i->user->user_name . ' checked into ' . $i->beer->beer_name . '<br />';
}
