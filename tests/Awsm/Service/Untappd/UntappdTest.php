<?php

/** Awsm_Service_Untappd */
require_once 'Awsm/Service/Untappd.php';

class Awsm_Service_Untappd_UntappdTest extends PHPUnit_Framework_TestCase
{
    const APIKEY = '';

    public function testInit()
    {
        $untappd = new Awsm_Service_Untappd(self::APIKEY);

    }

}
