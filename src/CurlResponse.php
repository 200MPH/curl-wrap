<?php
declare(strict_types=1);

namespace thm\curl;

use CurlHandle;

class CurlResponse {

    private  $response = null;
    private float $timeStart = 0;
    private float $timeStop = 0;

    public function __construct(private readonly CurlHandle $handle)
    {
        $this->timeStart = microtime(true);
        $this->response = curl_exec($this->handle);
        $this->timeStop = microtime(true);
    }

    public function getStatus(): int
    {
        $statusCode = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
        return $statusCode;
    }

    public function getBody(): ?string
    {
        return $this->response ?? null;
    }

    public function getInfo(): array
    {
        return curl_getinfo($this->handle);
    }

    /**
     * Get CURL error number
     *
     * @return int Error number
     */
    public function getErrorNo(): int
    {
        return curl_errno($this->handle);
    }

    /**
     * Get CURL error string
     *
     * @return string Error string
     */
    public function getError(): string
    {
        return curl_error($this->handle);
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
}