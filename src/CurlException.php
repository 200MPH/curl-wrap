<?php

/**
 * Curl Exception
 *
 * @author Wojciech Brozyna <wojciech.brozyna@gmail.com>
 */

declare(strict_types=1);

namespace thm\curl;

class CurlException extends \Exception
{
    /**
     * Function setopt() returned false
     * Posibble wrong parameter name
     * @var int
     */
    public const SETOP_FAIL = 99101;

    /**
     * Curl not initialized yet.
     * Curl::init() need to be executed first.
     * @var int
     */
    public const CURL_INIT_FAIL = 99102;
}
