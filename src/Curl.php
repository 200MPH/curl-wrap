<?php
declare(strict_types=1);

namespace thm\curl;

use CURLFile;
use CurlHandle;
use SensitiveParameter;
use SensitiveParameterValue;

class Curl
{

    public const GET = 'GET';
    public const POST = 'POST';
    public const PUT = 'PUT';
    public const PATCH = 'PATCH';
    public const DELETE = 'DELETE';

    /**
     * @var CurlHandle
     */
    private $curlHandle;

    /**
     * @var array
     */
    private array $headers = [];

    /**
     * Init cURL
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->curlHandle = curl_init($url);

        if(!$this->curlHandle)
        {
            $this->throwNotInitializedException();
        }

        // set default timeout to 5 seconds
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
        $ok = curl_setopt($this->curlHandle, $curlOption, $curlOptionValue);
        if ($ok !== true) {
            $errno = curl_errno($this->curlHandle);
            $error = curl_error($this->curlHandle);

            $msg = sprintf(
                'curl_setopt failed for option %d (%s). errno=%d error="%s"',
                $curlOption,
                $errno,
                $error,
                $errno
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
     * @param array<mixed> $params
     * @return Curl
     */
    public function setParameters(array $params): Curl
    {
        $this->setopt(CURLOPT_POSTFIELDS, http_build_query($params));
        return $this;
    }

    /**
     * Close CURL session
     *
     * @return void
     */
    public function close(): void
    {
        curl_close($this->curlHandle);
    }

    /**
     * Send GET Method
     * @param array<array> $headers [optional]
     */
    public function get(array $headers = []): CurlResponse
    {
        $this->setopt(CURLOPT_RETURNTRANSFER, 1);
        $this->setopt(CURLOPT_HTTPHEADER , $headers);

        return new CurlResponse($this->curlHandle);
    }

    /**
     * Send POST Method
     *
     * @param array<array> $postFields [optional]
     * @param CURLFile[] $files [optional]
     * @param array<array> $headers [optional]
     */
    public function post(array $postFields = [], array $files = [], array $headers = []): CurlResponse
    {
        $files = array_filter($files, fn($v, $k): bool => $v instanceof CURLFile, ARRAY_FILTER_USE_BOTH);

        if(!empty($files)) {
            foreach($files as $index => $file) {
                $postFields["files[$index]"] = $file;
            }
        } else {
            $postFields = http_build_query($postFields);
        }

        $this->preparePost($postFields, $headers);
        return new CurlResponse($this->curlHandle);
    }

    /**
     * Send PUT Method
     * @param array<array> $postFields [optional]
     * @param CURLFile[] $files [optional]
     * @param array<array> $headers [optional]
     */
    public function put(array $postFields = [], array $files = [], array $headers = []): CurlResponse
    {
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'PUT');
        return $this->post($postFields, $files, $headers);
    }

    /**
     * Send PATCH Method
     * @param array<array> $postFields [optional]
     * @param CURLFile[] $files [optional]
     * @param array<array> $headers [optional]
     */
    public function patch(array $postFields = [], array $files = [], array $headers = []): CurlResponse
    {
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        return $this->post($postFields, $files, $headers);
    }

    /**
     * Send DELETE Method
     * @param array<array> $postFields [optional]
     * @param CURLFile[] $files [optional]
     * @param array<array> $headers [optional]
     */
    public function delete(array $postFields = [], array $files = [], array $headers = []): CurlResponse
    {
        $this->setopt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        return $this->post($postFields, $files, $headers);
    }

    /**
     * Post JSON
     *
     * @param mixed $data JSON-serializable
     * @param string $method POST|PUT|PATCH
     */
    public function json(#[SensitiveParameter] $data, string $method = 'POST'): CurlResponse
    {
        // NOTE: logic intentionally left as-is to preserve behavior
        if($method !== 'POST' || $method !== 'PUT' || $method !== 'PATCH') {
            $method = 'POST';
        }

        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        $this->preparePost($data, $headers);
        $this->setopt(CURLOPT_CUSTOMREQUEST, $method);
        return $this->request();
    }

    /**
     * Send binary data
     */
    public function binary(#[SensitiveParameter] string $data, array $headers = []): CurlResponse
    {
        $headers[] = 'Content-Type: application/octet-stream';
        $headers[] = 'Content-Length: ' . strlen($data);
        $this->preparePost($data, $headers);

        return new CurlResponse($this->curlHandle);
    }

    /**
     * Send custom request
     *
     * @return CurlResponse
     */
    public function request(): CurlResponse
    {
        return new CurlResponse($this->curlHandle);
    }

    /**
     * Set Bearer authorisation
     *
     * @param string $token
     * @return Curl
     */
    public function setBearerAuth(#[SensitiveParameter] string $token): Curl
    {      
        $spv = new SensitiveParameterValue($token);
        $this->headers = [
            "Authorization: Bearer {$spv->getValue()}"
        ];

        return $this;
    }

    /**
     * Prepare POST request
     * @mixed $postFields
     * @param array $headers
     */
    private function preparePost($postFields, array $headers): void
    {
        $headers = array_merge($headers, $this->headers);
        $headers = array_unique($headers);
        $this->setopt(CURLOPT_RETURNTRANSFER, 1);
        $this->setopt(CURLOPT_HTTPHEADER , $headers);
        $this->setopt(CURLOPT_POST , true);
        $this->setopt(CURLOPT_POSTFIELDS , $postFields);
    }

    /**
     * Throw Exception
     *
     * @throws CurlException cURL not initialized yet
     */
    private function throwNotInitializedException(): never
    {
        throw new CurlException('cURL not initialized yet', CurlException::CURL_INIT_FAIL);
    }
}
