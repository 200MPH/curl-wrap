<?php

use thm\curl\Curl;

// Include auto loader for testing purpose.
require_once '../../vendor/autoload.php';

// Initialize object
$curl = new Curl("http://api.nbp.pl/api/exchangerates/tables/A/today/?format=json");

// Change default timeout (5 seconds)
$curl->setTimeout(2);

// If you need pass parameters, simple call
// $curl->setParameters(['foo' => 1, 'bar' => 2, 'baz' => 3]);

// Get results
$results = $curl->call();

// If you want to call without getting results
// $curl->call(false);
// or
// $curl->callQuiet();

var_dump($results);
var_dump($curl->getResponseTime());
var_dump($curl->getErrorNo());
var_dump($curl->getError());
