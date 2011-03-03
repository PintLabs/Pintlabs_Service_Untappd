<?php 

error_reporting( E_ALL | E_STRICT );
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
date_default_timezone_set('America/New_York');

$awsmPath = realpath(dirname(dirname(__FILE__))) . '/library';

$includePaths = array($awsmPath, get_include_path());

define('TESTS_PATH', realpath(dirname(__FILE__)));

set_include_path(implode(PATH_SEPARATOR, $includePaths));

unset($awsmPath, $includePaths);