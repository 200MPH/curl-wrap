# PHP CURL Wrapper

![coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)

This is simple wrapper for PHP CURL.

    <?php
    
    use thm\curl\Curl;
    
    // Include auto loader for testing purpose. 
    // I assume you've already done it in your project.
    require_once '../vendor/autoload.php';
    
    // Initialize object
    $curl = new Curl("http://api.nbp.pl/api/exchangerates/tables/A/today/?format=json");
    
    // Change default timeout (5 seconds) - optional
    $curl->setTimeout(2);
        
    // GET 
    $response = $curl->get();

    // POST
    //$response = $curl->post();
    //$response = $curl->post($params, $files, $headers);

    // PATCH
    //$response = $curl->patch();

    // PUT
    //$response = $curl->put();

    // DELETE
    //$response = $curl->delete();
    
    $status = $response->getStatus();
    $body = $response->getBody();
    $time = $response->getResponseTime();
    $errorStr = $response->getError();
    $errorCode = $response->getErrorNo();
    $moreInfo = $response->getInfo();
    


