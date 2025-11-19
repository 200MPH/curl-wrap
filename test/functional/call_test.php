<?php

use thm\curl\Curl;

// Include auto loader for testing purpose.
require_once '../../vendor/autoload.php';

// Initialize object
$curl = new Curl("http://api.nbp.pl/api/exchangerates/tables/A/today/?format=json");

// Change default timeout
$curl->setTimeout(2);

// GET method
$results = $curl->get();

var_dump($results->getBody());
print(PHP_EOL);
print('Time: ' . $results->getResponseTime() . PHP_EOL);
print('Error No: ' . $results->getErrorNo() . PHP_EOL);
print('Error message: ' . $results->getError() . PHP_EOL);
