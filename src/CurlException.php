<?php

/**
 * Curl Exception
 *
 * @author Wojciech Brozyna <wojciech.brozyna@gmail.com>
 */

namespace app\Vendor\Curl;

class CurlException extends \Exception {
    
    /**
     * Function setopt() returned false
     * Posibble wrong parameter name
     */
    const SETOP_FAIL = 99101;
    
    /**
     * Curl not initialized yet.
     * Curl::init() need to be executed first.
     */
    const CURL_INIT_FAIL = 99102;
    
}
