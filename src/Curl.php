<?php

/**
 * Curl client
 *
 * @author Wojciech Brozyna <wojciech.brozyna@gmail.com>
 */

namespace thm\curl;

use CurlHandle;

class Curl
{
    /**
     * @var CurlHandle
     */
    private $curlHandle = false;

    /**
     * Start request time
     */
    private $timeStart = 0;

    /**
     * Stop request time
     */
    private $timeStop = 0;

    /**
     * Init cURL
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->curlHandle = curl_init($url);
        $this->setTimeout(5);
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Set CURL timeout.
     * Default value is set to 5 seconds, when object initialized.
     *
     * @param int $seconds
     * @see Curl::__construct()
     * @return Curl
     */
    public function setTimeout(int $seconds): Curl
    {
        return $this->setopt(CURLOPT_TIMEOUT, $seconds);
    }

    /**
     * Set cURL option
     *
     * @param int $curlOption
     * @param mixed $curlOptionValue
     *
     * @return Curl
     * @throws CurlException CURL setopt() function returned False
     */
    public function setopt(int $curlOption, mixed $curlOptionValue): Curl
    {
        if ($this->curlHandle === null) {
            $this->throwNotInitializedException();
        }

        $ok = curl_setopt($this->curlHandle, $curlOption, $curlOptionValue);
        if ($ok !== true) {
            $errno = curl_errno($this->curlHandle);
            $error = curl_error($this->curlHandle);

            $msg = sprintf(
                'curl_setopt failed for option %d (%s). errno=%d error="%s"',
                $curlOption,
                $errno,
                $error
            );

            throw new CurlException($msg, CurlException::SETOP_FAIL);
        }

        return $this;
    }

    /**
     * Set POST parameters.
     * This function will let you pass parameters fast and easy,
     * so no need to set additional CURL options.
     *
     * @param array $params
     * @return Curl
     */
    public function setParameters(array $params): Curl
    {
        $this->setopt(CURLOPT_POSTFIELDS, http_build_query($params));
        return $this;
    }

    /**
     * Get CURL error number
     *
     * @return int Error number
     */
    public function getErrorNo(): int
    {
        if ($this->curlHandle !== false) {
            return (curl_errno($this->curlHandle));
        } else {
            $this->throwNotInitializedException();
        }
    }

    /**
     * Get CURL error string
     *
     * @return string Error string
     */
    public function getError(): string
    {
        if ($this->curlHandle !== false) {
            return (curl_error($this->curlHandle));
        } else {
            $this->throwNotInitializedException();
        }
    }

    /**
     * Close CURL session
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->curlHandle !== false) {
            curl_close($this->curlHandle);
            $this->curlHandle = false;
        }
    }

    /**
     * CURL execute - get results
     *
     * @param bool $return [optional] Set option CURLOPT_RETURNTRANSFER. Default set to true.
     * @return mixed <b>TRUE</b> on success or <b>FALSE</b> on failure. However, if the <b>CURLOPT_RETURNTRANSFER</b>
     * option is set, it will return the result on success, <b>FALSE</b> on failure.
     */
    public function exec(bool $return = true): bool|string
    {
        if ($this->curlHandle !== false) {
            $this->timeStart = microtime(true);
            if ($return === true) {
                $this->setopt(CURLOPT_RETURNTRANSFER, 1);
            }
            $response = curl_exec($this->curlHandle);
            $this->timeStop = microtime(true);
            return $response;
        } else {
            $this->throwNotInitializedException();
        }
    }

    /**
     * Alias for exec()
     *
     * @param bool $return [optional] Set option CURLOPT_RETURNTRANSFER. Default set to true.
     * @see Curl::exec()
     */
    public function call(bool $return = true): bool|string
    {
        return $this->exec($return);
    }

    /**
     * Quiet call
     * @return bool
     */
    public function callQuiet(): bool
    {
        return $this->call(false);
    }

    /**
     * Response time
     * @return float
     */
    public function getResponseTime(): float
    {
        $time = $this->timeStop - $this->timeStart;
        return round($time, 3);
    }

    /**
     * Throw Exception
     *
     * @throws Exception cURL not initialized yet
     */
    private function throwNotInitializedException(): never
    {
        throw new CurlException('cURL not initialized yet', CurlException::CURL_INIT_FAIL);
    }
}
