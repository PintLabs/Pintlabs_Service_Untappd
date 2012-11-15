<?php

/** Pintlabs_Service_Untappd */
require_once 'Pintlabs/Service/Untappd.php';

class Pintlabs_Service_Untappd_UntappdTest extends PHPUnit_Framework_TestCase
{
    const APIKEY = '';

    public function testInit()
    {
        $options = array();
        
        $untappd = new Pintlabs_Service_Untappd($options);
    }

}
