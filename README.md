# CURL PHP Wrapper

This is simple wrapper for PHP CURL.

    <?php
    
    use thm\curl\Curl;
    
    // initialize object
    $curl = new Curl('http://api.nbp.pl/api/exchangerates/tables/A/today/');
    
    // if you need pass parameters, simple call
    $curl->setParameters(['foo' => 1, 'bar' => 2, 'baz' => 3]);
    
    // get results
    $results = $curl->call();
    
    // if you want to call without getting result
    $curl->call(false);
    
    var_dump($results);
    var_dump($curl->getErrorNo());
    var_dump($curl->getError());

